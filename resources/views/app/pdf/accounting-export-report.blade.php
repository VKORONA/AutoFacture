<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport de contrôle comptable</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 11px; }
        h1 { margin: 0; font-size: 22px; color: #1d4ed8; }
        h2 { margin: 24px 0 8px; font-size: 14px; color: #172033; }
        .subtitle { margin-top: 5px; color: #64748b; }
        .card { margin-top: 18px; padding: 14px; border: 1px solid #dbe4f0; border-radius: 8px; background: #f8fafc; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 12px; font-weight: bold; background: #dcfce7; color: #166534; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #eff6ff; color: #1e3a8a; }
        .number { text-align: right; }
        .warning { margin-top: 8px; padding: 8px; background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .notice { margin-top: 22px; padding: 10px; background: #f1f5f9; color: #475569; font-size: 9px; line-height: 1.5; }
    </style>
</head>
<body>
    <h1>Rapport de contrôle du lot comptable</h1>
    <div class="subtitle">AutoFacture — {{ $company->name }}</div>

    <div class="card">
        <table>
            <tr><th>Identifiant du lot</th><td>{{ $batch->uuid }}</td></tr>
            <tr><th>Période</th><td>{{ $batch->period_start->format('d/m/Y') }} au {{ $batch->period_end->format('d/m/Y') }}</td></tr>
            <tr><th>Profil d’export</th><td>{{ $batch->profile }}</td></tr>
            <tr><th>SIREN</th><td>{{ $company->siren ?: 'Non renseigné' }}</td></tr>
            <tr><th>Date de génération</th><td>{{ now()->format('d/m/Y H:i') }}</td></tr>
        </table>
    </div>

    <h2>Contrôle d’équilibre</h2>
    <span class="status">Équilibré</span>
    <table>
        <tr><th>Total débit</th><td class="number">{{ number_format($manifest['control']['total_debit_cents'] / 100, 2, ',', ' ') }} €</td></tr>
        <tr><th>Total crédit</th><td class="number">{{ number_format($manifest['control']['total_credit_cents'] / 100, 2, ',', ' ') }} €</td></tr>
        <tr><th>Écart</th><td class="number">{{ number_format($manifest['control']['difference_cents'] / 100, 2, ',', ' ') }} €</td></tr>
    </table>

    <h2>Documents inclus</h2>
    <table>
        <tr><th>Factures</th><td class="number">{{ $manifest['counts']['invoices'] }}</td></tr>
        <tr><th>Avoirs</th><td class="number">{{ $manifest['counts']['credit_notes'] }}</td></tr>
        <tr><th>Règlements</th><td class="number">{{ $manifest['counts']['payments'] }}</td></tr>
        <tr><th>Lignes comptables</th><td class="number">{{ $manifest['counts']['entries'] }}</td></tr>
    </table>

    @if (! empty($manifest['warnings']))
        <h2>Avertissements</h2>
        @foreach ($manifest['warnings'] as $warning)
            <div class="warning">{{ $warning }}</div>
        @endforeach
    @endif

    <div class="notice">
        {{ $manifest['notice'] }}<br>
        Les fichiers sont fournis pour l’import chez le cabinet comptable. Les comptes, journaux et règles d’affectation doivent être validés par le professionnel chargé de la comptabilité.
    </div>
</body>
</html>
