#region Using declarations
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.ComponentModel.DataAnnotations;
using System.IO;
using System.Linq;
using System.Text;
using System.Windows;
using System.Windows.Media;
using NinjaTrader.Cbi;
using NinjaTrader.Data;
using NinjaTrader.Gui.Chart;
using NinjaTrader.Gui.Tools;
using NinjaTrader.NinjaScript;
using NinjaTrader.NinjaScript.DrawingTools;
#endregion

namespace NinjaTrader.NinjaScript.Strategies
{
    public class ZNFixingCorridorL1V5MultiOrder : Strategy
    {
        private enum FixState
        {
            ActiveUntested,
            Approaching,
            FirstContact,
            Rejected,
            BreakoutPending,
            AcceptedAbove,
            AcceptedBelow,
            RetestAbove,
            RetestBelow,
            MultiTested,
            Invalidated
        }

        private enum SetupKind
        {
            None,
            Rejection,
            PolarityRetest
        }

        private enum DirectionLock
        {
            None,
            Long,
            Short,
            Conflict
        }

        private sealed class ProxyAccumulator
        {
            public DateTime EventNY;
            public DateTime EndNY;
            public double PriceVolume;
            public long Volume;
            public bool Finalized;
            public double Price;
        }

        private sealed class AutoFixingDay
        {
            public DateTime NYDate;
            public ProxyAccumulator Shanghai;
            public ProxyAccumulator LondonAM;
            public ProxyAccumulator LondonPM;
            public DateTime LondonPMPreRangeStartNY;
            public double LondonPMPreHigh = double.MinValue;
            public double LondonPMPreLow = double.MaxValue;
            public bool LondonPMHasPreRange;
            public bool MorningContextPublished;
            public bool LondonPMContextPublished;
        }

        private sealed class TapePrint
        {
            public DateTime Time;
            public double Price;
            public long Volume;
            public int Side;
        }

        private sealed class Flow
        {
            public long Buy;
            public long Sell;
            public long Unknown;
            public double FirstPrice;
            public double LastPrice;
            public double MoveTicks;

            public long Total
            {
                get { return Buy + Sell + Unknown; }
            }

            public long Classified
            {
                get { return Buy + Sell; }
            }

            public double BuyRatio
            {
                get { return Classified > 0 ? (double)Buy / Classified : 0.5; }
            }

            public double SellRatio
            {
                get { return Classified > 0 ? (double)Sell / Classified : 0.5; }
            }
        }

        private sealed class FixLevel
        {
            public string Key;
            public DateTime NYDate;
            public string Label;
            public double Price;
            public DateTime ActivatedAt;
            public double QualityWeight;
            public FixState State;
            public int AcceptedSide;

            public bool EpisodeActive;
            public int EpisodeId;
            public DateTime EpisodeStart;
            public bool ContactLatched;
            public int Contacts;
            public int AttemptsThisEpisode;

            public long EpisodeVolumeAt;
            public long EpisodeVolumeAbove;
            public long EpisodeVolumeBelow;
            public int EpisodeTradesAbove;
            public int EpisodeTradesBelow;

            public int BreakDirection;
            public long BreakVolumeBeyond;
            public int BreakTradesBeyond;
            public double BreakExtreme;

            public bool Armed;
            public int ArmedDirection;
            public SetupKind ArmedSetup;
            public double ArmedScore;
            public int ArmedEpisodeId;
            public bool ShadowLogged;

            public int RejectCount;
            public DateTime LastRejectTime;
            public string LastRejectReason;
        }

        private sealed class Candidate
        {
            public FixLevel Level;
            public SetupKind Setup;
            public bool IsLong;
            public double Score;
            public double RoomTicks;
        }

        private sealed class EntryContext
        {
            public string Signal;
            public FixLevel Level;
            public SetupKind Setup;
            public bool IsLong;
            public double Score;
            public int EpisodeId;
            public Order EntryOrder;
            public double LimitPrice;
            public int RequestedQuantity;
            public int FilledQuantity;
            public int OpenQuantity;
            public bool CountedFill;
            public bool CancelRequested;
            public bool ExitRequested;
            public bool Terminal;
            public double EntryPrice;
            public long PostBuy;
            public long PostSell;
            public long PostUnknown;
            public double PostHigh;
            public double PostLow;
        }

        private readonly object sync = new object();
        private readonly Queue<TapePrint> prints = new Queue<TapePrint>();
        private readonly Dictionary<long, long> sessionVolumeByTick = new Dictionary<long, long>();
        private readonly List<FixLevel> fixings = new List<FixLevel>();
        private readonly Dictionary<DateTime, AutoFixingDay> autoDays = new Dictionary<DateTime, AutoFixingDay>();
        private readonly Dictionary<string, EntryContext> entries = new Dictionary<string, EntryContext>();

        private TimeZoneInfo nyZone;
        private TimeZoneInfo londonZone;
        private TimeZoneInfo shanghaiZone;

        private double bid;
        private double ask;
        private long bidSize;
        private long askSize;
        private double last;
        private double previousTradePrice;
        private int previousTradeSide;
        private DateTime marketTime;

        private int signalSequence;
        private int tradesThisSession;
        private int consecutiveLosses;
        private int processedTrades;
        private DateTime currentSessionDate = Core.Globals.MinDate;
        private string decision = "Initialisation";

        protected override void OnStateChange()
        {
            if (State == State.SetDefaults)
            {
                Name = "ZNFixingCorridorL1V5MultiOrder";
                Description = "ZN Level 1 fixing strategy with direction lock and up to seven same-direction pending orders.";
                Calculate = Calculate.OnEachTick;
                EntriesPerDirection = 20;
                EntryHandling = EntryHandling.AllEntries;
                IsExitOnSessionCloseStrategy = true;
                ExitOnSessionCloseSeconds = 30;
                IsFillLimitOnTouch = false;
                StartBehavior = StartBehavior.WaitUntilFlat;
                RealtimeErrorHandling = RealtimeErrorHandling.StopCancelCloseIgnoreRejects;
                StopTargetHandling = StopTargetHandling.PerEntryExecution;
                TimeInForce = TimeInForce.Day;
                BarsRequiredToTrade = 5;

                Quantity = 1;
                TargetTicks = 2;
                StopTicks = 2;
                EntryLeadTicks = 3;
                MaxPendingOrdersSameDirection = 7;
                MaxApproachDistanceTicks = 8;
                MinApproachDistanceTicks = 1;
                ContactToleranceTicks = 0.50;
                ContactResetDistanceTicks = 3;
                BreakAcceptanceTicks = 2;
                AcceptanceZoneTicks = 5;
                MinVolumeBeyondForAcceptance = 120;
                MinTradesBeyondForAcceptance = 8;
                MaximumRetests = 1;
                MaxAttemptsPerEpisode = 1;

                TapeWindowMs = 2000;
                SlowWindowMs = 10000;
                MinimumFlowVolume = 80;
                RejectionMinVolume = 120;
                RejectionMaxEfficiency = 0.018;
                BreakoutMinEfficiency = 0.035;
                AdverseFlowRatio = 0.70;
                MaximumApproachVelocity = 3.0;
                MinimumAbsorptionScore = 0.55;
                MinimumEntryScore = 70;
                UseTopOfBookScore = true;
                MaximumSpreadTicks = 2.0;

                MinimumRoomToNextLevelTicks = 3;
                ConfluenceDistanceTicks = 2;
                MinimumPendingSpacingTicks = 1;
                MissedBounceTicks = 2;

                InvalidationTicks = 1;
                MinInvalidationVolume = 100;
                NoBounceVolume = 220;
                ExpectedBounceTicks = 1;
                FailedBounceAdverseRatio = 0.68;

                TradeStart = 10000;
                TradeEnd = 160000;
                MaxTradesPerSession = 7;
                MaxConsecutiveLosses = 2;
                RestrictToZN = true;
                UseRejectionSetups = true;
                UsePolarityRetests = true;

                FixingProxyMinutes = 2;
                LondonPMPreRangeMinutes = 15;
                UseShanghaiPM = true;
                UseLondonAM = true;
                UseLondonPM = true;
                UseLondonPMRangeEdges = true;
                UseContextMidLevels = false;
                FixingLevelWeight = 1.00;
                RangeEdgeWeight = 0.85;
                MidLevelWeight = 0.65;

                DrawFixingStates = true;
                ShowPanel = true;
                WriteCsv = true;
                WriteShadowCandidates = true;
                CsvFileName = "ZN_FixingCorridor_L1_V5_MultiOrder.csv";
            }
            else if (State == State.Configure)
            {
                AddDataSeries(BarsPeriodType.Minute, 1);
            }
            else if (State == State.DataLoaded)
            {
                nyZone = FindZone("Eastern Standard Time", TimeZoneInfo.Local);
                londonZone = FindZone("GMT Standard Time", TimeZoneInfo.Utc);
                shanghaiZone = FindZone("China Standard Time", TimeZoneInfo.Utc);
            }
            else if (State == State.Terminated)
            {
                List<EntryContext> snapshot;
                lock (sync)
                    snapshot = entries.Values.ToList();

                foreach (EntryContext context in snapshot)
                {
                    if (context.EntryOrder != null && IsWorkingState(context.EntryOrder.OrderState))
                        CancelOrder(context.EntryOrder);
                }
            }
        }

        private TimeZoneInfo FindZone(string id, TimeZoneInfo fallback)
        {
            try { return TimeZoneInfo.FindSystemTimeZoneById(id); }
            catch
            {
                Print("[ZN FIX V5] Fuseau introuvable : " + id);
                return fallback;
            }
        }

        private DateTime ConvertToNY(DateTime sourceLocal, TimeZoneInfo sourceZone)
        {
            return TimeZoneInfo.ConvertTime(DateTime.SpecifyKind(sourceLocal, DateTimeKind.Unspecified), sourceZone, nyZone);
        }

        private DateTime FindEventForNYDate(DateTime nyDate, TimeZoneInfo sourceZone, int hour, int minute)
        {
            for (int shift = -1; shift <= 1; shift++)
            {
                DateTime sourceDate = nyDate.Date.AddDays(shift);
                DateTime local = new DateTime(sourceDate.Year, sourceDate.Month, sourceDate.Day, hour, minute, 0);
                DateTime converted = ConvertToNY(local, sourceZone);
                if (converted.Date == nyDate.Date)
                    return converted;
            }

            DateTime fallback = new DateTime(nyDate.Year, nyDate.Month, nyDate.Day, hour, minute, 0);
            return ConvertToNY(fallback, sourceZone);
        }

        private AutoFixingDay GetOrCreateAutoDay(DateTime nyDate)
        {
            nyDate = nyDate.Date;
            AutoFixingDay day;
            if (autoDays.TryGetValue(nyDate, out day))
                return day;

            DateTime shanghaiEvent = FindEventForNYDate(nyDate, shanghaiZone, 14, 15);
            DateTime londonAMEvent = FindEventForNYDate(nyDate, londonZone, 10, 30);
            DateTime londonPMEvent = FindEventForNYDate(nyDate, londonZone, 15, 0);
            int proxyMinutes = Math.Max(1, FixingProxyMinutes);

            day = new AutoFixingDay
            {
                NYDate = nyDate,
                Shanghai = new ProxyAccumulator { EventNY = shanghaiEvent, EndNY = shanghaiEvent.AddMinutes(proxyMinutes) },
                LondonAM = new ProxyAccumulator { EventNY = londonAMEvent, EndNY = londonAMEvent.AddMinutes(proxyMinutes) },
                LondonPM = new ProxyAccumulator { EventNY = londonPMEvent, EndNY = londonPMEvent.AddMinutes(proxyMinutes) },
                LondonPMPreRangeStartNY = londonPMEvent.AddMinutes(-Math.Max(1, LondonPMPreRangeMinutes))
            };

            autoDays[nyDate] = day;
            return day;
        }

        private void ProcessOneMinuteProxyBar()
        {
            if (CurrentBars == null || CurrentBars.Length < 2 || CurrentBars[1] < 1)
                return;

            DateTime barEndNY = Times[1][0];
            DateTime barStartNY = barEndNY.AddMinutes(-1);
            AutoFixingDay day = GetOrCreateAutoDay(barStartNY.Date);

            AccumulateProxyBar(day.Shanghai, barStartNY);
            AccumulateProxyBar(day.LondonAM, barStartNY);
            AccumulateProxyBar(day.LondonPM, barStartNY);

            if (barStartNY >= day.LondonPMPreRangeStartNY && barStartNY < day.LondonPM.EventNY)
            {
                day.LondonPMPreHigh = Math.Max(day.LondonPMPreHigh, Highs[1][0]);
                day.LondonPMPreLow = Math.Min(day.LondonPMPreLow, Lows[1][0]);
                day.LondonPMHasPreRange = day.LondonPMPreHigh > day.LondonPMPreLow;
            }

            FinalizeProxyIfDue(day.Shanghai, barEndNY);
            FinalizeProxyIfDue(day.LondonAM, barEndNY);
            FinalizeProxyIfDue(day.LondonPM, barEndNY);

            if (day.Shanghai.Finalized && UseShanghaiPM)
                AddAutomaticLevel(day.NYDate, "SH_PM", "Shanghai PM VWAP " + FixingProxyMinutes + "m", day.Shanghai.Price, day.Shanghai.EndNY, FixingLevelWeight);

            if (day.LondonAM.Finalized && UseLondonAM)
                AddAutomaticLevel(day.NYDate, "LON_AM", "Londres AM VWAP " + FixingProxyMinutes + "m", day.LondonAM.Price, day.LondonAM.EndNY, FixingLevelWeight);

            if (!day.MorningContextPublished && day.Shanghai.Finalized && day.LondonAM.Finalized)
            {
                day.MorningContextPublished = true;
                if (UseContextMidLevels)
                {
                    double mid = Instrument.MasterInstrument.RoundToTickSize((day.Shanghai.Price + day.LondonAM.Price) / 2.0);
                    AddAutomaticLevel(day.NYDate, "AM_MID", "Milieu Shanghai/Londres AM", mid, day.LondonAM.EndNY, MidLevelWeight);
                }
            }

            if (day.LondonPM.Finalized && UseLondonPM)
                AddAutomaticLevel(day.NYDate, "LON_PM", "Londres PM VWAP " + FixingProxyMinutes + "m", day.LondonPM.Price, day.LondonPM.EndNY, FixingLevelWeight);

            if (!day.LondonPMContextPublished && day.LondonPM.Finalized)
            {
                day.LondonPMContextPublished = true;
                if (UseLondonPMRangeEdges && day.LondonPMHasPreRange)
                {
                    AddAutomaticLevel(day.NYDate, "LON_PM_RH", "Londres PM pré-range haut", day.LondonPMPreHigh, day.LondonPM.EndNY, RangeEdgeWeight);
                    AddAutomaticLevel(day.NYDate, "LON_PM_RL", "Londres PM pré-range bas", day.LondonPMPreLow, day.LondonPM.EndNY, RangeEdgeWeight);
                }

                if (UseContextMidLevels && day.LondonPMHasPreRange)
                {
                    double mid = Instrument.MasterInstrument.RoundToTickSize((day.LondonPMPreHigh + day.LondonPMPreLow) / 2.0);
                    AddAutomaticLevel(day.NYDate, "LON_PM_MID", "Londres PM pré-range milieu", mid, day.LondonPM.EndNY, MidLevelWeight);
                }
            }

            foreach (DateTime key in autoDays.Keys.Where(x => x < barStartNY.Date.AddDays(-5)).ToList())
                autoDays.Remove(key);
        }

        private void AccumulateProxyBar(ProxyAccumulator proxy, DateTime barStartNY)
        {
            if (proxy == null || proxy.Finalized)
                return;
            if (barStartNY < proxy.EventNY || barStartNY >= proxy.EndNY)
                return;

            long volume = Math.Max(1L, (long)Volumes[1][0]);
            double typical = (Highs[1][0] + Lows[1][0] + Closes[1][0]) / 3.0;
            proxy.PriceVolume += typical * volume;
            proxy.Volume += volume;
        }

        private void FinalizeProxyIfDue(ProxyAccumulator proxy, DateTime barEndNY)
        {
            if (proxy == null || proxy.Finalized || barEndNY < proxy.EndNY || proxy.Volume <= 0)
                return;

            proxy.Price = Instrument.MasterInstrument.RoundToTickSize(proxy.PriceVolume / proxy.Volume);
            proxy.Finalized = true;
        }

        private void AddAutomaticLevel(DateTime nyDate, string sourceKey, string label, double price, DateTime activationNY, double weight)
        {
            string key = nyDate.ToString("yyyyMMdd") + "_" + sourceKey;
            lock (sync)
            {
                if (fixings.Any(x => x.Key == key))
                    return;

                fixings.Add(new FixLevel
                {
                    Key = key,
                    NYDate = nyDate.Date,
                    Label = label,
                    Price = Instrument.MasterInstrument.RoundToTickSize(price),
                    ActivatedAt = activationNY,
                    QualityWeight = Math.Max(0.10, Math.Min(2.0, weight)),
                    State = FixState.ActiveUntested
                });
            }
        }

        protected override void OnBarUpdate()
        {
            if (BarsInProgress == 1)
            {
                try { ProcessOneMinuteProxyBar(); }
                catch (Exception ex)
                {
                    decision = "ERREUR PROXY M1 : " + ex.Message;
                    Print("[ZN FIX V5] " + decision + " | " + ex.StackTrace);
                }
                return;
            }

            if (BarsInProgress != 0 || CurrentBar < BarsRequiredToTrade)
                return;

            try
            {
                if (Bars.IsFirstBarOfSession || currentSessionDate != Time[0].Date)
                    ResetSession(Time[0]);

                UpdateLevelStates(Time[0]);

                if (State == State.Realtime)
                {
                    EnforceDirectionConsistency();
                    ManageWorkingOrders();
                    ManageOpenEntries();
                    SearchAndSubmitOrders(Time[0]);
                }

                if (DrawFixingStates)
                    DrawLevels();
                if (ShowPanel)
                    DrawPanel();
            }
            catch (Exception ex)
            {
                decision = "ERREUR PRINCIPALE : " + ex.Message;
                Print("[ZN FIX V5] " + decision + " | " + ex.StackTrace);
            }
        }

        protected override void OnMarketData(MarketDataEventArgs e)
        {
            if (e == null)
                return;

            lock (sync)
            {
                marketTime = e.Time;

                if (e.MarketDataType == MarketDataType.Bid)
                {
                    bid = e.Price;
                    bidSize = e.Volume;
                    return;
                }

                if (e.MarketDataType == MarketDataType.Ask)
                {
                    ask = e.Price;
                    askSize = e.Volume;
                    return;
                }

                if (e.MarketDataType != MarketDataType.Last || e.Volume <= 0)
                    return;

                last = e.Price;
                int side = ClassifyTrade(e.Price);

                prints.Enqueue(new TapePrint { Time = e.Time, Price = e.Price, Volume = e.Volume, Side = side });
                previousTradePrice = e.Price;
                if (side != 0)
                    previousTradeSide = side;

                PurgePrints(e.Time.AddMilliseconds(-Math.Max(SlowWindowMs, TapeWindowMs) - 1000));

                long volumeKey = PriceKey(e.Price);
                long oldVolume;
                sessionVolumeByTick.TryGetValue(volumeKey, out oldVolume);
                sessionVolumeByTick[volumeKey] = oldVolume + e.Volume;

                foreach (FixLevel level in fixings.ToArray())
                {
                    if (!level.EpisodeActive)
                        continue;

                    double distanceTicks = (e.Price - level.Price) / TickSize;
                    if (Math.Abs(distanceTicks) <= 0.25)
                        level.EpisodeVolumeAt += e.Volume;
                    else if (distanceTicks > 0)
                    {
                        level.EpisodeVolumeAbove += e.Volume;
                        level.EpisodeTradesAbove++;
                    }
                    else
                    {
                        level.EpisodeVolumeBelow += e.Volume;
                        level.EpisodeTradesBelow++;
                    }

                    if (level.BreakDirection > 0 && distanceTicks >= BreakAcceptanceTicks && distanceTicks <= AcceptanceZoneTicks)
                    {
                        level.BreakVolumeBeyond += e.Volume;
                        level.BreakTradesBeyond++;
                    }
                    else if (level.BreakDirection < 0 && distanceTicks <= -BreakAcceptanceTicks && distanceTicks >= -AcceptanceZoneTicks)
                    {
                        level.BreakVolumeBeyond += e.Volume;
                        level.BreakTradesBeyond++;
                    }
                }

                foreach (EntryContext context in entries.Values.Where(x => !x.Terminal && x.OpenQuantity > 0).ToArray())
                {
                    if (side > 0) context.PostBuy += e.Volume;
                    else if (side < 0) context.PostSell += e.Volume;
                    else context.PostUnknown += e.Volume;

                    context.PostHigh = Math.Max(context.PostHigh, e.Price);
                    context.PostLow = Math.Min(context.PostLow, e.Price);
                }
            }
        }

        private int ClassifyTrade(double price)
        {
            if (ask > 0 && price >= ask - TickSize * 0.1)
                return 1;
            if (bid > 0 && price <= bid + TickSize * 0.1)
                return -1;

            if (previousTradePrice > 0)
            {
                if (price > previousTradePrice) return 1;
                if (price < previousTradePrice) return -1;
            }

            return previousTradeSide;
        }

        private void ResetSession(DateTime now)
        {
            currentSessionDate = now.Date;
            tradesThisSession = 0;
            consecutiveLosses = 0;

            lock (sync)
            {
                sessionVolumeByTick.Clear();
                fixings.RemoveAll(x => x.NYDate != now.Date);
            }

            decision = "Nouvelle séance - attente fixings";
        }

        private void StartEpisode(FixLevel level, DateTime now)
        {
            level.EpisodeActive = true;
            level.EpisodeId++;
            level.EpisodeStart = now;
            level.ContactLatched = false;
            level.AttemptsThisEpisode = 0;
            level.EpisodeVolumeAt = 0;
            level.EpisodeVolumeAbove = 0;
            level.EpisodeVolumeBelow = 0;
            level.EpisodeTradesAbove = 0;
            level.EpisodeTradesBelow = 0;
            level.BreakDirection = 0;
            level.BreakVolumeBeyond = 0;
            level.BreakTradesBeyond = 0;
            level.BreakExtreme = 0;
            level.Armed = false;
            level.ArmedDirection = 0;
            level.ArmedSetup = SetupKind.None;
            level.ArmedScore = 0;
            level.ArmedEpisodeId = level.EpisodeId;
            level.ShadowLogged = false;

            if (level.AcceptedSide == 0)
                level.State = FixState.Approaching;
        }

        private void StartBreak(FixLevel level, int direction, double price)
        {
            if (level.BreakDirection == direction)
                return;

            level.BreakDirection = direction;
            level.BreakVolumeBeyond = 0;
            level.BreakTradesBeyond = 0;
            level.BreakExtreme = price;
            level.State = FixState.BreakoutPending;
        }

        private void UpdateLevelStates(DateTime now)
        {
            double price = last > 0 ? last : Close[0];
            FixLevel[] snapshot;
            lock (sync)
                snapshot = fixings.ToArray();

            foreach (FixLevel level in snapshot)
            {
                double distanceTicks = (price - level.Price) / TickSize;
                double absoluteDistance = Math.Abs(distanceTicks);

                if (!level.EpisodeActive && absoluteDistance <= MaxApproachDistanceTicks)
                    StartEpisode(level, now);

                if (!level.EpisodeActive)
                    continue;

                if (!level.ContactLatched && absoluteDistance <= ContactToleranceTicks)
                {
                    level.ContactLatched = true;
                    level.Contacts++;

                    if (level.Contacts > MaximumRetests + 1)
                        level.State = FixState.MultiTested;
                    else if (level.AcceptedSide > 0)
                        level.State = FixState.RetestAbove;
                    else if (level.AcceptedSide < 0)
                        level.State = FixState.RetestBelow;
                    else
                        level.State = FixState.FirstContact;
                }

                if (distanceTicks >= BreakAcceptanceTicks)
                {
                    StartBreak(level, 1, price);
                    level.BreakExtreme = Math.Max(level.BreakExtreme, price);
                }
                else if (distanceTicks <= -BreakAcceptanceTicks)
                {
                    StartBreak(level, -1, price);
                    level.BreakExtreme = level.BreakExtreme == 0 ? price : Math.Min(level.BreakExtreme, price);
                }
                else if (level.BreakDirection != 0 && level.AcceptedSide == 0)
                {
                    level.BreakDirection = 0;
                    level.BreakVolumeBeyond = 0;
                    level.BreakTradesBeyond = 0;
                }

                if (level.BreakDirection > 0
                    && level.BreakVolumeBeyond >= MinVolumeBeyondForAcceptance
                    && level.BreakTradesBeyond >= MinTradesBeyondForAcceptance)
                {
                    level.AcceptedSide = 1;
                    level.State = FixState.AcceptedAbove;
                }
                else if (level.BreakDirection < 0
                    && level.BreakVolumeBeyond >= MinVolumeBeyondForAcceptance
                    && level.BreakTradesBeyond >= MinTradesBeyondForAcceptance)
                {
                    level.AcceptedSide = -1;
                    level.State = FixState.AcceptedBelow;
                }

                if (level.ContactLatched && absoluteDistance >= ContactResetDistanceTicks)
                {
                    if (level.AcceptedSide == 0 && level.State != FixState.MultiTested)
                        level.State = FixState.Rejected;

                    level.EpisodeActive = false;
                    level.ContactLatched = false;
                    level.Armed = false;
                }
                else if (!level.ContactLatched && absoluteDistance > MaxApproachDistanceTicks + 1)
                {
                    level.EpisodeActive = false;
                    level.Armed = false;
                }
            }
        }

        private void SearchAndSubmitOrders(DateTime now)
        {
            if (!CanTradeNow(now))
                return;

            Flow fast = GetFlow(TapeWindowMs);
            Flow slow = GetFlow(SlowWindowMs);
            if (fast.Total < MinimumFlowVolume)
            {
                decision = "ATTENTE : volume Level 1 " + fast.Total + "/" + MinimumFlowVolume;
                return;
            }

            List<Candidate> candidates = BuildCandidates(fast, slow);
            if (candidates.Count == 0)
            {
                decision = "ATTENTE : aucun setup validé";
                return;
            }

            DirectionLock direction = GetDirectionLock();
            if (direction == DirectionLock.Conflict)
            {
                decision = "BLOQUÉ : conflit de direction";
                return;
            }

            if (direction == DirectionLock.None)
            {
                Candidate strongest = candidates.OrderByDescending(x => x.Score).First();
                direction = strongest.IsLong ? DirectionLock.Long : DirectionLock.Short;
            }

            candidates = candidates
                .Where(x => direction == DirectionLock.Long ? x.IsLong : !x.IsLong)
                .OrderByDescending(x => x.Score)
                .ToList();

            int remainingSlots = MaxPendingOrdersSameDirection - CountPendingEntries(direction);
            if (remainingSlots <= 0)
            {
                decision = "BLOQUÉ : maximum " + MaxPendingOrdersSameDirection + " ordres en attente " + direction;
                return;
            }

            int submitted = 0;
            foreach (Candidate candidate in candidates)
            {
                if (submitted >= remainingSlots)
                    break;
                if (HasOrderNearPrice(candidate.IsLong, candidate.Level.Price))
                    continue;
                if (SubmitPassive(candidate))
                    submitted++;
            }

            if (submitted > 0)
                decision = submitted + " ordre(s) " + direction + " envoyé(s)";
        }

        private List<Candidate> BuildCandidates(Flow fast, Flow slow)
        {
            List<Candidate> result = new List<Candidate>();
            double price = last > 0 ? last : Close[0];
            double velocity = Math.Abs(slow.MoveTicks) / Math.Max(1.0, SlowWindowMs / 1000.0);
            if (velocity > MaximumApproachVelocity)
                return result;

            FixLevel[] snapshot;
            lock (sync)
                snapshot = fixings.ToArray();

            foreach (FixLevel level in snapshot)
            {
                if (!level.EpisodeActive
                    || level.State == FixState.MultiTested
                    || level.State == FixState.Invalidated
                    || level.AttemptsThisEpisode >= MaxAttemptsPerEpisode)
                    continue;

                double distance = (price - level.Price) / TickSize;
                double absoluteDistance = Math.Abs(distance);
                if (absoluteDistance < MinApproachDistanceTicks || absoluteDistance > MaxApproachDistanceTicks)
                    continue;

                long classified = Math.Max(1L, fast.Classified);
                double efficiency = Math.Abs(fast.MoveTicks) / classified;
                double volumeFactor = Math.Min(1.0, (double)fast.Total / Math.Max(1L, RejectionMinVolume));
                double inefficiencyFactor = 1.0 - Math.Min(1.0, efficiency / Math.Max(0.0001, RejectionMaxEfficiency));
                double localBonus = Math.Min(0.25, (double)level.EpisodeVolumeAt / Math.Max(1.0, RejectionMinVolume * 4.0));
                double absorption = Math.Min(1.0, volumeFactor * inefficiencyFactor + localBonus);

                Candidate candidate = null;
                if (UseRejectionSetups && level.AcceptedSide == 0)
                {
                    if (distance > 0 && slow.MoveTicks < 0 && fast.SellRatio >= 0.50 && fast.SellRatio < AdverseFlowRatio
                        && efficiency <= RejectionMaxEfficiency && absorption >= MinimumAbsorptionScore)
                        candidate = BuildCandidate(level, SetupKind.Rejection, true, absorption, fast.SellRatio);
                    else if (distance < 0 && slow.MoveTicks > 0 && fast.BuyRatio >= 0.50 && fast.BuyRatio < AdverseFlowRatio
                        && efficiency <= RejectionMaxEfficiency && absorption >= MinimumAbsorptionScore)
                        candidate = BuildCandidate(level, SetupKind.Rejection, false, absorption, fast.BuyRatio);
                }

                if (UsePolarityRetests)
                {
                    if (level.AcceptedSide > 0 && distance > 0 && slow.MoveTicks < 0 && fast.SellRatio < AdverseFlowRatio)
                        candidate = BuildCandidate(level, SetupKind.PolarityRetest, true, absorption, fast.SellRatio);
                    else if (level.AcceptedSide < 0 && distance < 0 && slow.MoveTicks > 0 && fast.BuyRatio < AdverseFlowRatio)
                        candidate = BuildCandidate(level, SetupKind.PolarityRetest, false, absorption, fast.BuyRatio);
                }

                if (candidate == null || candidate.Score < MinimumEntryScore)
                    continue;

                if (absoluteDistance > EntryLeadTicks)
                {
                    level.Armed = true;
                    level.ArmedDirection = candidate.IsLong ? 1 : -1;
                    level.ArmedSetup = candidate.Setup;
                    level.ArmedScore = candidate.Score;
                    level.ArmedEpisodeId = level.EpisodeId;

                    if (WriteShadowCandidates && !level.ShadowLogged)
                    {
                        level.ShadowLogged = true;
                        LogEvent(level, "ARMED", candidate.IsLong ? 1 : -1, candidate.Score, "Setup armé à distance");
                    }
                    continue;
                }

                bool armedValid = level.Armed
                    && level.ArmedEpisodeId == level.EpisodeId
                    && level.ArmedDirection == (candidate.IsLong ? 1 : -1);

                if (armedValid)
                    candidate.Score = Math.Max(candidate.Score, level.ArmedScore);

                result.Add(candidate);
            }

            return result;
        }

        private Candidate BuildCandidate(FixLevel level, SetupKind setup, bool isLong, double absorption, double adverseRatio)
        {
            double room = GetRoomToNextLevelTicks(level, isLong);
            if (room > 0 && room < MinimumRoomToNextLevelTicks)
                return null;

            int confluence = GetConfluenceCount(level);
            double baseScore = setup == SetupKind.PolarityRetest ? 76.0 : 55.0;
            double score = baseScore
                + absorption * 25.0
                + Math.Max(0, AdverseFlowRatio - adverseRatio) * 20.0
                + Math.Min(12.0, Math.Max(0, confluence - 1) * 4.0)
                + Math.Min(10.0, Math.Max(0, room) / 2.0)
                - Math.Max(0, level.Contacts - 1) * 10.0;

            if (UseTopOfBookScore && bidSize + askSize > 0)
            {
                double bidRatio = (double)bidSize / (bidSize + askSize);
                score += isLong ? (bidRatio - 0.5) * 20.0 : (0.5 - bidRatio) * 20.0;
            }

            score *= level.QualityWeight;
            return new Candidate { Level = level, Setup = setup, IsLong = isLong, Score = score, RoomTicks = room };
        }

        private bool SubmitPassive(Candidate candidate)
        {
            DirectionLock direction = GetDirectionLock();
            if (direction == DirectionLock.Long && !candidate.IsLong)
                return false;
            if (direction == DirectionLock.Short && candidate.IsLong)
                return false;

            DirectionLock wanted = candidate.IsLong ? DirectionLock.Long : DirectionLock.Short;
            if (CountPendingEntries(wanted) >= MaxPendingOrdersSameDirection)
                return false;

            double validatedPrice;
            string reason;
            if (!ValidatePassiveLimit(candidate.IsLong, candidate.Level.Price, out validatedPrice, out reason))
            {
                decision = "ORDRE NON ENVOYÉ : " + reason;
                LogEvent(candidate.Level, "ORDER_BLOCKED", candidate.IsLong ? 1 : -1, candidate.Score, reason);
                return false;
            }

            string signal = (candidate.IsLong ? "ZN_FIX_LONG_" : "ZN_FIX_SHORT_") + (++signalSequence).ToString("0000");
            EntryContext context = new EntryContext
            {
                Signal = signal,
                Level = candidate.Level,
                Setup = candidate.Setup,
                IsLong = candidate.IsLong,
                Score = candidate.Score,
                EpisodeId = candidate.Level.EpisodeId,
                LimitPrice = validatedPrice,
                RequestedQuantity = Math.Max(1, Quantity)
            };

            candidate.Level.AttemptsThisEpisode++;
            lock (sync)
                entries[signal] = context;

            SetStopLoss(signal, CalculationMode.Ticks, Math.Max(1, StopTicks), false);
            SetProfitTarget(signal, CalculationMode.Ticks, Math.Max(1, TargetTicks));

            try
            {
                if (candidate.IsLong)
                    EnterLongLimit(0, true, context.RequestedQuantity, validatedPrice, signal);
                else
                    EnterShortLimit(0, true, context.RequestedQuantity, validatedPrice, signal);

                LogEvent(candidate.Level, "ORDER", candidate.IsLong ? 1 : -1, candidate.Score, signal);
                return true;
            }
            catch (Exception ex)
            {
                context.Terminal = true;
                lock (sync)
                    entries.Remove(signal);
                decision = "EXCEPTION ORDRE : " + ex.Message;
                Print("[ZN FIX V5] " + decision);
                return false;
            }
        }

        private bool ValidatePassiveLimit(bool isLong, double requestedPrice, out double validatedPrice, out string reason)
        {
            validatedPrice = Instrument.MasterInstrument.RoundToTickSize(requestedPrice);
            reason = string.Empty;
            double currentBid = bid > 0 ? bid : GetCurrentBid();
            double currentAsk = ask > 0 ? ask : GetCurrentAsk();

            if (validatedPrice <= 0)
            {
                reason = "prix invalide";
                return false;
            }
            if (currentBid <= 0 || currentAsk <= 0 || currentAsk < currentBid)
            {
                reason = "Bid/Ask indisponible";
                return false;
            }

            double spreadTicks = (currentAsk - currentBid) / TickSize;
            if (spreadTicks > MaximumSpreadTicks)
            {
                reason = "spread " + spreadTicks.ToString("0.0") + " ticks";
                return false;
            }
            if (isLong && validatedPrice >= currentAsk - TickSize * 0.1)
            {
                reason = "Buy Limit non passif";
                return false;
            }
            if (!isLong && validatedPrice <= currentBid + TickSize * 0.1)
            {
                reason = "Sell Limit non passif";
                return false;
            }

            return true;
        }

        private void ManageWorkingOrders()
        {
            double price = last > 0 ? last : Close[0];
            Flow fast = GetFlow(TapeWindowMs);
            double efficiency = Math.Abs(fast.MoveTicks) / Math.Max(1L, fast.Classified);
            List<EntryContext> snapshot;

            lock (sync)
                snapshot = entries.Values.Where(x => !x.Terminal && x.FilledQuantity < x.RequestedQuantity).ToList();

            foreach (EntryContext context in snapshot)
            {
                if (context.EntryOrder == null || !IsWorkingState(context.EntryOrder.OrderState) || context.CancelRequested)
                    continue;

                bool episodeExpired = context.Level.EpisodeId != context.EpisodeId || !context.Level.EpisodeActive;
                bool levelInvalid = context.Level.State == FixState.MultiTested || context.Level.State == FixState.Invalidated;
                bool acceptedThrough = context.IsLong
                    ? price <= context.Level.Price - BreakAcceptanceTicks * TickSize && fast.SellRatio >= AdverseFlowRatio && efficiency >= BreakoutMinEfficiency
                    : price >= context.Level.Price + BreakAcceptanceTicks * TickSize && fast.BuyRatio >= AdverseFlowRatio && efficiency >= BreakoutMinEfficiency;
                bool adverseFlow = context.IsLong
                    ? fast.SellRatio >= AdverseFlowRatio && fast.MoveTicks <= -InvalidationTicks
                    : fast.BuyRatio >= AdverseFlowRatio && fast.MoveTicks >= InvalidationTicks;
                bool missedBounce = context.Level.ContactLatched
                    && (context.IsLong
                        ? price >= context.Level.Price + MissedBounceTicks * TickSize
                        : price <= context.Level.Price - MissedBounceTicks * TickSize);

                if (episodeExpired || levelInvalid || acceptedThrough || adverseFlow || missedBounce)
                {
                    context.CancelRequested = true;
                    CancelOrder(context.EntryOrder);
                    string cause = missedBounce ? "rebond parti sans fill"
                        : acceptedThrough ? "acceptation à travers"
                        : adverseFlow ? "flux adverse"
                        : "épisode terminé";
                    LogEvent(context.Level, "CANCEL", context.IsLong ? 1 : -1, context.Score, cause);
                }
            }
        }

        private void ManageOpenEntries()
        {
            double price = last > 0 ? last : Close[0];
            List<EntryContext> snapshot;
            lock (sync)
                snapshot = entries.Values.Where(x => !x.Terminal && x.OpenQuantity > 0 && !x.ExitRequested).ToList();

            foreach (EntryContext context in snapshot)
            {
                long classified = context.PostBuy + context.PostSell;
                if (classified < MinInvalidationVolume)
                    continue;

                double buyRatio = (double)context.PostBuy / classified;
                double sellRatio = (double)context.PostSell / classified;
                double mfe = context.IsLong
                    ? (context.PostHigh - context.EntryPrice) / TickSize
                    : (context.EntryPrice - context.PostLow) / TickSize;

                bool wrongSide = context.IsLong
                    ? price <= context.Level.Price - InvalidationTicks * TickSize && sellRatio >= AdverseFlowRatio
                    : price >= context.Level.Price + InvalidationTicks * TickSize && buyRatio >= AdverseFlowRatio;
                bool noBounce = classified >= NoBounceVolume && mfe < ExpectedBounceTicks
                    && (context.IsLong
                        ? sellRatio >= FailedBounceAdverseRatio && price <= context.EntryPrice
                        : buyRatio >= FailedBounceAdverseRatio && price >= context.EntryPrice);
                bool failedBounce = mfe >= ExpectedBounceTicks
                    && (context.IsLong
                        ? price <= context.EntryPrice && sellRatio >= FailedBounceAdverseRatio
                        : price >= context.EntryPrice && buyRatio >= FailedBounceAdverseRatio);

                if (!wrongSide && !noBounce && !failedBounce)
                    continue;

                context.ExitRequested = true;
                if (context.IsLong)
                    ExitLong("INV_" + context.Signal, context.Signal);
                else
                    ExitShort("INV_" + context.Signal, context.Signal);

                LogEvent(context.Level, "INVALIDATION", context.IsLong ? 1 : -1, context.Score, "prix + volume");
            }
        }

        private void EnforceDirectionConsistency()
        {
            DirectionLock direction = GetDirectionLock();
            if (direction != DirectionLock.Conflict)
                return;

            decision = "ERREUR : ordres opposés détectés";
            List<EntryContext> snapshot;
            lock (sync)
                snapshot = entries.Values.ToList();

            bool keepLong = Position.MarketPosition == MarketPosition.Long;
            bool keepShort = Position.MarketPosition == MarketPosition.Short;
            if (!keepLong && !keepShort)
            {
                EntryContext oldest = snapshot.Where(x => !x.Terminal).OrderBy(x => x.Signal).FirstOrDefault();
                if (oldest != null)
                    keepLong = oldest.IsLong;
            }

            foreach (EntryContext context in snapshot)
            {
                bool cancel = (keepLong && !context.IsLong) || (keepShort && context.IsLong);
                if (cancel && context.EntryOrder != null && IsWorkingState(context.EntryOrder.OrderState))
                {
                    context.CancelRequested = true;
                    CancelOrder(context.EntryOrder);
                }
            }
        }

        private DirectionLock GetDirectionLock()
        {
            if (Position.MarketPosition == MarketPosition.Long)
                return DirectionLock.Long;
            if (Position.MarketPosition == MarketPosition.Short)
                return DirectionLock.Short;

            bool hasLong;
            bool hasShort;
            lock (sync)
            {
                hasLong = entries.Values.Any(x => !x.Terminal && x.IsLong);
                hasShort = entries.Values.Any(x => !x.Terminal && !x.IsLong);
            }

            if (hasLong && hasShort) return DirectionLock.Conflict;
            if (hasLong) return DirectionLock.Long;
            if (hasShort) return DirectionLock.Short;
            return DirectionLock.None;
        }

        private int CountPendingEntries(DirectionLock direction)
        {
            lock (sync)
            {
                return entries.Values.Count(x => !x.Terminal
                    && x.FilledQuantity < x.RequestedQuantity
                    && (direction == DirectionLock.Long ? x.IsLong : !x.IsLong));
            }
        }

        private bool HasOrderNearPrice(bool isLong, double price)
        {
            lock (sync)
            {
                return entries.Values.Any(x => !x.Terminal
                    && x.IsLong == isLong
                    && x.FilledQuantity < x.RequestedQuantity
                    && Math.Abs(x.LimitPrice - price) < MinimumPendingSpacingTicks * TickSize);
            }
        }

        private bool CanTradeNow(DateTime now)
        {
            int time = ToTime(now);
            if (time < TradeStart || time > TradeEnd)
            {
                decision = "BLOQUÉ : plage horaire";
                return false;
            }
            if (tradesThisSession >= MaxTradesPerSession)
            {
                decision = "BLOQUÉ : maximum de trades";
                return false;
            }
            if (consecutiveLosses >= MaxConsecutiveLosses)
            {
                decision = "BLOQUÉ : pertes consécutives";
                return false;
            }
            if (RestrictToZN && !Instrument.MasterInstrument.Name.StartsWith("ZN", StringComparison.OrdinalIgnoreCase))
            {
                decision = "BLOQUÉ : instrument non ZN";
                return false;
            }

            lock (sync)
            {
                if (fixings.Count == 0)
                {
                    decision = "ATTENTE : aucun fixing";
                    return false;
                }
            }

            return true;
        }

        private double GetRoomToNextLevelTicks(FixLevel level, bool isLong)
        {
            lock (sync)
            {
                IEnumerable<FixLevel> candidates = fixings.Where(x => x != level && x.State != FixState.Invalidated);
                if (isLong)
                {
                    FixLevel next = candidates.Where(x => x.Price > level.Price).OrderBy(x => x.Price).FirstOrDefault();
                    return next == null ? 999 : (next.Price - level.Price) / TickSize;
                }

                FixLevel previous = candidates.Where(x => x.Price < level.Price).OrderByDescending(x => x.Price).FirstOrDefault();
                return previous == null ? 999 : (level.Price - previous.Price) / TickSize;
            }
        }

        private int GetConfluenceCount(FixLevel level)
        {
            lock (sync)
                return fixings.Count(x => Math.Abs(x.Price - level.Price) <= ConfluenceDistanceTicks * TickSize);
        }

        private Flow GetFlow(int windowMs)
        {
            lock (sync)
            {
                Flow flow = new Flow();
                DateTime cutoff = marketTime.AddMilliseconds(-windowMs);
                bool first = true;

                foreach (TapePrint print in prints)
                {
                    if (print.Time < cutoff)
                        continue;
                    if (first)
                    {
                        flow.FirstPrice = print.Price;
                        first = false;
                    }
                    flow.LastPrice = print.Price;
                    if (print.Side > 0) flow.Buy += print.Volume;
                    else if (print.Side < 0) flow.Sell += print.Volume;
                    else flow.Unknown += print.Volume;
                }

                flow.MoveTicks = first ? 0 : (flow.LastPrice - flow.FirstPrice) / TickSize;
                return flow;
            }
        }

        private void PurgePrints(DateTime cutoff)
        {
            while (prints.Count > 0 && prints.Peek().Time < cutoff)
                prints.Dequeue();
        }

        private long PriceKey(double price)
        {
            return (long)Math.Round(price / TickSize, MidpointRounding.AwayFromZero);
        }

        private bool IsWorkingState(OrderState state)
        {
            return state == OrderState.Submitted
                || state == OrderState.Accepted
                || state == OrderState.Working
                || state == OrderState.PartFilled;
        }

        protected override void OnOrderUpdate(Order order, double limitPrice, double stopPrice, int quantity, int filled,
            double averageFillPrice, OrderState orderState, DateTime time, ErrorCode error, string nativeError)
        {
            if (order == null)
                return;

            EntryContext context;
            lock (sync)
            {
                if (!entries.TryGetValue(order.Name, out context))
                    return;
                context.EntryOrder = order;
            }

            bool rejected = orderState == OrderState.Rejected || error != ErrorCode.NoError;
            if (rejected)
            {
                string reason = "Error=" + error
                    + " | Native=" + (string.IsNullOrWhiteSpace(nativeError) ? "non renseigné" : nativeError)
                    + " | Limit=" + Instrument.MasterInstrument.FormatPrice(limitPrice);

                context.Terminal = true;
                context.Level.RejectCount++;
                context.Level.LastRejectTime = time;
                context.Level.LastRejectReason = reason;
                decision = "ORDRE REJETÉ : " + reason;
                Print("[ZN FIX V5] " + decision);
                LogEvent(context.Level, "ORDER_REJECTED", context.IsLong ? 1 : -1, context.Score, reason);

                lock (sync)
                    entries.Remove(context.Signal);
                return;
            }

            if (orderState == OrderState.Cancelled && context.FilledQuantity == 0)
            {
                context.Terminal = true;
                lock (sync)
                    entries.Remove(context.Signal);
            }
        }

        protected override void OnExecutionUpdate(Execution execution, string executionId, double price, int quantity,
            MarketPosition marketPosition, string orderId, DateTime time)
        {
            if (execution == null || execution.Order == null)
                return;

            EntryContext context;
            lock (sync)
            {
                if (entries.TryGetValue(execution.Order.Name, out context))
                {
                    int oldFilled = context.FilledQuantity;
                    int newFilled = oldFilled + quantity;
                    context.EntryPrice = oldFilled <= 0
                        ? price
                        : ((context.EntryPrice * oldFilled) + (price * quantity)) / newFilled;
                    context.FilledQuantity = newFilled;
                    context.OpenQuantity += quantity;

                    if (!context.CountedFill)
                    {
                        context.CountedFill = true;
                        context.PostHigh = context.EntryPrice;
                        context.PostLow = context.EntryPrice;
                        tradesThisSession++;
                        LogEvent(context.Level, "FILL", context.IsLong ? 1 : -1, context.Score, context.Signal);
                    }
                    return;
                }
            }

            string fromEntry = execution.Order.FromEntrySignal;
            if (string.IsNullOrWhiteSpace(fromEntry))
                return;

            lock (sync)
            {
                if (!entries.TryGetValue(fromEntry, out context))
                    return;

                context.OpenQuantity = Math.Max(0, context.OpenQuantity - quantity);
                if (context.OpenQuantity == 0 && (context.EntryOrder == null || !IsWorkingState(context.EntryOrder.OrderState)))
                {
                    context.Terminal = true;
                    LogEvent(context.Level, "EXIT", context.IsLong ? 1 : -1, context.Score, execution.Order.Name);
                    entries.Remove(fromEntry);
                }
            }
        }

        protected override void OnPositionUpdate(Position position, double averagePrice, int quantity, MarketPosition marketPosition)
        {
            if (marketPosition == MarketPosition.Flat)
            {
                while (SystemPerformance.AllTrades.Count > processedTrades)
                {
                    Trade trade = SystemPerformance.AllTrades[processedTrades];
                    consecutiveLosses = trade.ProfitCurrency < 0 ? consecutiveLosses + 1 : 0;
                    processedTrades++;
                }

                lock (sync)
                {
                    foreach (EntryContext context in entries.Values.Where(x => x.OpenQuantity > 0).ToList())
                    {
                        context.OpenQuantity = 0;
                        if (context.EntryOrder == null || !IsWorkingState(context.EntryOrder.OrderState))
                        {
                            context.Terminal = true;
                            entries.Remove(context.Signal);
                        }
                    }
                }
            }
        }

        private void DrawLevels()
        {
            FixLevel[] snapshot;
            lock (sync)
                snapshot = fixings.ToArray();

            foreach (FixLevel level in snapshot)
            {
                Brush brush = Brushes.DodgerBlue;
                if (level.AcceptedSide > 0) brush = Brushes.LimeGreen;
                else if (level.AcceptedSide < 0) brush = Brushes.OrangeRed;
                else if (level.State == FixState.MultiTested) brush = Brushes.Gray;

                double thickness = Math.Max(TickSize * 0.035, 0.0000001);
                Draw.Rectangle(this, "FIX_" + level.Key, false,
                    level.ActivatedAt, level.Price + thickness,
                    Time[0], level.Price - thickness,
                    brush, brush, 20);

                Draw.Text(this, "FIX_TXT_" + level.Key, false,
                    level.Label + " | " + level.State + " | C" + level.Contacts + " | E" + level.EpisodeId,
                    0, level.Price, 0, brush, new SimpleFont("Arial", 11), TextAlignment.Left,
                    Brushes.Transparent, Brushes.Transparent, 0);
            }
        }

        private void DrawPanel()
        {
            Flow fast = GetFlow(TapeWindowMs);
            DirectionLock direction = GetDirectionLock();
            int longPending = CountPendingEntries(DirectionLock.Long);
            int shortPending = CountPendingEntries(DirectionLock.Short);
            StringBuilder text = new StringBuilder();

            text.AppendLine("ZN FIXING CORRIDOR L1 V5 MULTI");
            text.AppendLine("État : " + State + " | " + Instrument.FullName);
            text.AppendLine("Décision : " + decision);
            text.AppendLine("Verrou : " + direction + " | Pending L " + longPending + "/" + MaxPendingOrdersSameDirection
                + " | S " + shortPending + "/" + MaxPendingOrdersSameDirection);
            text.AppendLine("Tape " + TapeWindowMs + "ms | Buy " + fast.BuyRatio.ToString("P0")
                + " | Sell " + fast.SellRatio.ToString("P0") + " | Vol " + fast.Total
                + " | Δ " + fast.MoveTicks.ToString("0.0") + "t");
            text.AppendLine("BidSize " + bidSize + " | AskSize " + askSize
                + " | Trades " + tradesThisSession + "/" + MaxTradesPerSession);

            FixLevel[] snapshot;
            lock (sync)
            {
                snapshot = fixings.OrderBy(x => Math.Abs((last > 0 ? last : Close[0]) - x.Price)).Take(5).ToArray();
            }

            foreach (FixLevel level in snapshot)
            {
                text.AppendLine(level.Label + " " + Instrument.MasterInstrument.FormatPrice(level.Price)
                    + " | " + level.State + " | Ep " + level.EpisodeId
                    + " | Tent. " + level.AttemptsThisEpisode);
            }

            Draw.TextFixed(this, "ZN_FIX_PANEL_V5", text.ToString(), TextPosition.TopRight,
                Brushes.White, new SimpleFont("Consolas", 12), Brushes.DimGray, Brushes.Black, 85);
        }

        private void LogEvent(FixLevel level, string eventName, int direction, double score, string note)
        {
            if (!WriteCsv || level == null)
                return;

            try
            {
                string directory = Path.Combine(NinjaTrader.Core.Globals.UserDataDir, "logs");
                Directory.CreateDirectory(directory);
                string path = Path.Combine(directory, CsvFileName);
                bool writeHeader = !File.Exists(path);

                using (StreamWriter writer = new StreamWriter(path, true, Encoding.UTF8))
                {
                    if (writeHeader)
                    {
                        writer.WriteLine("time;instrument;event;key;label;price;state;episode;contacts;attempts;"
                            + "direction;score;volumeAt;episodeAbove;episodeBelow;breakVolume;breakTrades;note");
                    }

                    writer.WriteLine(string.Join(";", new string[]
                    {
                        DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss.fff"),
                        Instrument.FullName,
                        eventName,
                        level.Key,
                        level.Label.Replace(";", ","),
                        level.Price.ToString("0.########"),
                        level.State.ToString(),
                        level.EpisodeId.ToString(),
                        level.Contacts.ToString(),
                        level.AttemptsThisEpisode.ToString(),
                        direction.ToString(),
                        score.ToString("0.00"),
                        level.EpisodeVolumeAt.ToString(),
                        level.EpisodeVolumeAbove.ToString(),
                        level.EpisodeVolumeBelow.ToString(),
                        level.BreakVolumeBeyond.ToString(),
                        level.BreakTradesBeyond.ToString(),
                        (note ?? string.Empty).Replace(";", ",")
                    }));
                }
            }
            catch (Exception ex)
            {
                Print("[ZN FIX V5] CSV : " + ex.Message);
            }
        }

        #region Properties

        [NinjaScriptProperty, Range(1, 20)]
        [Display(Name = "Quantité", GroupName = "01. Exécution", Order = 1)]
        public int Quantity { get; set; }

        [NinjaScriptProperty, Range(1, 20)]
        [Display(Name = "Objectif ticks", GroupName = "01. Exécution", Order = 2)]
        public int TargetTicks { get; set; }

        [NinjaScriptProperty, Range(1, 20)]
        [Display(Name = "Stop ticks", GroupName = "01. Exécution", Order = 3)]
        public int StopTicks { get; set; }

        [NinjaScriptProperty, Range(1, 10)]
        [Display(Name = "Placement anticipé ticks", GroupName = "01. Exécution", Order = 4)]
        public int EntryLeadTicks { get; set; }

        [NinjaScriptProperty, Range(1, 7)]
        [Display(Name = "Ordres en attente max même sens", GroupName = "01. Exécution", Order = 5)]
        public int MaxPendingOrdersSameDirection { get; set; }

        [NinjaScriptProperty, Range(1, 30)]
        [Display(Name = "Distance approche max", GroupName = "02. Niveau", Order = 1)]
        public int MaxApproachDistanceTicks { get; set; }

        [NinjaScriptProperty, Range(0, 10)]
        [Display(Name = "Distance approche min", GroupName = "02. Niveau", Order = 2)]
        public int MinApproachDistanceTicks { get; set; }

        [NinjaScriptProperty, Range(0.1, 2)]
        [Display(Name = "Tolérance contact", GroupName = "02. Niveau", Order = 3)]
        public double ContactToleranceTicks { get; set; }

        [NinjaScriptProperty, Range(1, 20)]
        [Display(Name = "Distance reset contact", GroupName = "02. Niveau", Order = 4)]
        public int ContactResetDistanceTicks { get; set; }

        [NinjaScriptProperty, Range(1, 10)]
        [Display(Name = "Acceptation au-delà", GroupName = "02. Niveau", Order = 5)]
        public int BreakAcceptanceTicks { get; set; }

        [NinjaScriptProperty, Range(2, 30)]
        [Display(Name = "Zone volume cassure", GroupName = "02. Niveau", Order = 6)]
        public int AcceptanceZoneTicks { get; set; }

        [NinjaScriptProperty, Range(1, 100000)]
        [Display(Name = "Volume mini cassure", GroupName = "02. Niveau", Order = 7)]
        public long MinVolumeBeyondForAcceptance { get; set; }

        [NinjaScriptProperty, Range(1, 100)]
        [Display(Name = "Trades mini cassure", GroupName = "02. Niveau", Order = 8)]
        public int MinTradesBeyondForAcceptance { get; set; }

        [NinjaScriptProperty, Range(0, 5)]
        [Display(Name = "Retests max", GroupName = "02. Niveau", Order = 9)]
        public int MaximumRetests { get; set; }

        [NinjaScriptProperty, Range(1, 5)]
        [Display(Name = "Tentatives par épisode", GroupName = "02. Niveau", Order = 10)]
        public int MaxAttemptsPerEpisode { get; set; }

        [NinjaScriptProperty, Range(100, 60000)]
        [Display(Name = "Fenêtre rapide ms", GroupName = "03. Level 1", Order = 1)]
        public int TapeWindowMs { get; set; }

        [NinjaScriptProperty, Range(500, 120000)]
        [Display(Name = "Fenêtre lente ms", GroupName = "03. Level 1", Order = 2)]
        public int SlowWindowMs { get; set; }

        [NinjaScriptProperty, Range(1, 100000)]
        [Display(Name = "Volume flux minimum", GroupName = "03. Level 1", Order = 3)]
        public long MinimumFlowVolume { get; set; }

        [NinjaScriptProperty, Range(1, 100000)]
        [Display(Name = "Volume rejet minimum", GroupName = "03. Level 1", Order = 4)]
        public long RejectionMinVolume { get; set; }

        [NinjaScriptProperty, Range(0.0001, 1)]
        [Display(Name = "Efficacité max rejet", GroupName = "03. Level 1", Order = 5)]
        public double RejectionMaxEfficiency { get; set; }

        [NinjaScriptProperty, Range(0.0001, 1)]
        [Display(Name = "Efficacité min cassure", GroupName = "03. Level 1", Order = 6)]
        public double BreakoutMinEfficiency { get; set; }

        [NinjaScriptProperty, Range(0.5, 1)]
        [Display(Name = "Ratio flux adverse", GroupName = "03. Level 1", Order = 7)]
        public double AdverseFlowRatio { get; set; }

        [NinjaScriptProperty, Range(0.1, 20)]
        [Display(Name = "Vitesse approche max", GroupName = "03. Level 1", Order = 8)]
        public double MaximumApproachVelocity { get; set; }

        [NinjaScriptProperty, Range(0, 1)]
        [Display(Name = "Absorption minimum", GroupName = "03. Level 1", Order = 9)]
        public double MinimumAbsorptionScore { get; set; }

        [NinjaScriptProperty, Range(0, 100)]
        [Display(Name = "Score entrée minimum", GroupName = "03. Level 1", Order = 10)]
        public double MinimumEntryScore { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Score top of book", GroupName = "03. Level 1", Order = 11)]
        public bool UseTopOfBookScore { get; set; }

        [NinjaScriptProperty, Range(0.5, 10)]
        [Display(Name = "Spread maximum ticks", GroupName = "03. Level 1", Order = 12)]
        public double MaximumSpreadTicks { get; set; }

        [NinjaScriptProperty, Range(1, 30)]
        [Display(Name = "Espace mini prochain niveau", GroupName = "04. Corridor", Order = 1)]
        public int MinimumRoomToNextLevelTicks { get; set; }

        [NinjaScriptProperty, Range(0, 10)]
        [Display(Name = "Distance confluence", GroupName = "04. Corridor", Order = 2)]
        public int ConfluenceDistanceTicks { get; set; }

        [NinjaScriptProperty, Range(0, 10)]
        [Display(Name = "Espacement ordres", GroupName = "04. Corridor", Order = 3)]
        public int MinimumPendingSpacingTicks { get; set; }

        [NinjaScriptProperty, Range(1, 10)]
        [Display(Name = "Rebond raté ticks", GroupName = "05. Invalidation", Order = 1)]
        public int MissedBounceTicks { get; set; }

        [NinjaScriptProperty, Range(1, 10)]
        [Display(Name = "Invalidation ticks", GroupName = "05. Invalidation", Order = 2)]
        public int InvalidationTicks { get; set; }

        [NinjaScriptProperty, Range(1, 100000)]
        [Display(Name = "Volume mini invalidation", GroupName = "05. Invalidation", Order = 3)]
        public long MinInvalidationVolume { get; set; }

        [NinjaScriptProperty, Range(1, 100000)]
        [Display(Name = "Volume sans rebond", GroupName = "05. Invalidation", Order = 4)]
        public long NoBounceVolume { get; set; }

        [NinjaScriptProperty, Range(1, 10)]
        [Display(Name = "Rebond attendu", GroupName = "05. Invalidation", Order = 5)]
        public int ExpectedBounceTicks { get; set; }

        [NinjaScriptProperty, Range(0.5, 1)]
        [Display(Name = "Ratio échec rebond", GroupName = "05. Invalidation", Order = 6)]
        public double FailedBounceAdverseRatio { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Rejets directs", GroupName = "06. Setups", Order = 1)]
        public bool UseRejectionSetups { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Retests polarité", GroupName = "06. Setups", Order = 2)]
        public bool UsePolarityRetests { get; set; }

        [NinjaScriptProperty, Range(0, 235959)]
        [Display(Name = "Début trading", GroupName = "07. Sécurité", Order = 1)]
        public int TradeStart { get; set; }

        [NinjaScriptProperty, Range(0, 235959)]
        [Display(Name = "Fin trading", GroupName = "07. Sécurité", Order = 2)]
        public int TradeEnd { get; set; }

        [NinjaScriptProperty, Range(1, 50)]
        [Display(Name = "Trades max séance", GroupName = "07. Sécurité", Order = 3)]
        public int MaxTradesPerSession { get; set; }

        [NinjaScriptProperty, Range(1, 20)]
        [Display(Name = "Pertes consécutives max", GroupName = "07. Sécurité", Order = 4)]
        public int MaxConsecutiveLosses { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Restreindre au ZN", GroupName = "07. Sécurité", Order = 5)]
        public bool RestrictToZN { get; set; }

        [NinjaScriptProperty, Range(1, 10)]
        [Display(Name = "Durée proxy VWAP", GroupName = "08. Fixings", Order = 1)]
        public int FixingProxyMinutes { get; set; }

        [NinjaScriptProperty, Range(1, 60)]
        [Display(Name = "Pré-range Londres PM", GroupName = "08. Fixings", Order = 2)]
        public int LondonPMPreRangeMinutes { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Shanghai PM", GroupName = "08. Fixings", Order = 3)]
        public bool UseShanghaiPM { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Londres AM", GroupName = "08. Fixings", Order = 4)]
        public bool UseLondonAM { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Londres PM", GroupName = "08. Fixings", Order = 5)]
        public bool UseLondonPM { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Bords pré-range PM", GroupName = "08. Fixings", Order = 6)]
        public bool UseLondonPMRangeEdges { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Milieux contextes", GroupName = "08. Fixings", Order = 7)]
        public bool UseContextMidLevels { get; set; }

        [NinjaScriptProperty, Range(0.1, 2)]
        [Display(Name = "Poids fixing", GroupName = "08. Fixings", Order = 8)]
        public double FixingLevelWeight { get; set; }

        [NinjaScriptProperty, Range(0.1, 2)]
        [Display(Name = "Poids bord range", GroupName = "08. Fixings", Order = 9)]
        public double RangeEdgeWeight { get; set; }

        [NinjaScriptProperty, Range(0.1, 2)]
        [Display(Name = "Poids milieu", GroupName = "08. Fixings", Order = 10)]
        public double MidLevelWeight { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Dessiner niveaux", GroupName = "09. Affichage", Order = 1)]
        public bool DrawFixingStates { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Afficher panneau", GroupName = "09. Affichage", Order = 2)]
        public bool ShowPanel { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Écrire CSV", GroupName = "09. Affichage", Order = 3)]
        public bool WriteCsv { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Journal Shadow", GroupName = "09. Affichage", Order = 4)]
        public bool WriteShadowCandidates { get; set; }

        [NinjaScriptProperty]
        [Display(Name = "Nom CSV", GroupName = "09. Affichage", Order = 5)]
        public string CsvFileName { get; set; }

        #endregion
    }
}
