<template>
  <BasePage>
    <div class="mx-auto w-full max-w-7xl space-y-6 pb-10">
      <section
        class="
          relative
          overflow-hidden
          rounded-[28px]
          bg-gradient-to-br
          from-slate-950
          via-blue-950
          to-indigo-900
          px-6
          py-8
          text-white
          shadow-2xl shadow-blue-950/20
          md:px-10
        "
      >
        <div
          class="
            absolute
            -right-20
            -top-24
            h-72
            w-72
            rounded-full
            bg-cyan-400/20
            blur-3xl
          "
        />
        <div
          class="
            absolute
            -bottom-32
            left-1/3
            h-72
            w-72
            rounded-full
            bg-violet-500/20
            blur-3xl
          "
        />

        <div
          class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center"
        >
          <div>
            <div
              class="
                mb-4
                inline-flex
                items-center
                gap-2
                rounded-full
                border border-white/15
                bg-white/10
                px-3
                py-1.5
                text-xs
                font-semibold
                uppercase
                tracking-[0.16em]
                text-cyan-100
              "
            >
              <span
                class="
                  h-2
                  w-2
                  rounded-full
                  bg-cyan-300
                  shadow-[0_0_16px_rgba(103,232,249,0.9)]
                "
              />
              Facturation électronique française
            </div>
            <h1 class="max-w-3xl text-3xl font-bold tracking-tight md:text-4xl">
              Connectez AutoFacture à SUPER PDP, simplement
            </h1>
            <p
              class="
                mt-4
                max-w-3xl
                text-sm
                leading-7
                text-blue-100
                md:text-base
              "
            >
              Votre entreprise garde son propre compte SUPER PDP. AutoFacture ne
              collecte aucune pièce d’identité et vous guide jusqu’à la
              connexion technique.
            </p>
          </div>

          <div
            class="
              min-w-[230px]
              rounded-2xl
              border border-white/15
              bg-white/10
              p-5
              backdrop-blur
            "
          >
            <div class="flex items-center justify-between gap-4">
              <span class="text-sm text-blue-100">État de la connexion</span>
              <span
                class="rounded-full px-3 py-1 text-xs font-semibold"
                :class="statusBadgeClass"
              >
                {{ statusLabel }}
              </span>
            </div>
            <div class="mt-4 text-2xl font-bold">SUPER PDP</div>
            <div class="mt-1 text-sm text-blue-100">
              {{ environmentLabel }}
            </div>
          </div>
        </div>
      </section>

      <div v-if="store.isLoading" class="grid gap-6 lg:grid-cols-3">
        <div
          class="h-72 animate-pulse rounded-3xl bg-slate-200 lg:col-span-2"
        />
        <div class="h-72 animate-pulse rounded-3xl bg-slate-200" />
      </div>

      <template v-else-if="store.connection">
        <section
          class="
            rounded-3xl
            border border-slate-200
            bg-white
            p-5
            shadow-sm
            md:p-7
          "
        >
          <div
            class="
              mb-6
              flex flex-col
              gap-3
              md:flex-row md:items-center md:justify-between
            "
          >
            <div>
              <h2 class="text-xl font-bold text-slate-900">
                Configuration guidée
              </h2>
              <p class="mt-1 text-sm text-slate-500">
                Trois étapes, environ cinq minutes. Votre progression est
                mémorisée.
              </p>
            </div>
            <button
              type="button"
              class="
                inline-flex
                items-center
                justify-center
                rounded-xl
                border border-blue-200
                bg-blue-50
                px-4
                py-2.5
                text-sm
                font-semibold
                text-blue-700
                transition
                hover:bg-blue-100
              "
              @click="showGuide = !showGuide"
            >
              {{
                showGuide
                  ? 'Masquer le guide détaillé'
                  : 'Voir le guide pas à pas'
              }}
            </button>
          </div>

          <div class="grid gap-4 md:grid-cols-3">
            <div
              v-for="step in steps"
              :key="step.number"
              class="relative overflow-hidden rounded-2xl border p-4 transition"
              :class="step.cardClass"
            >
              <div class="flex items-center gap-3">
                <span
                  class="
                    flex
                    h-9
                    w-9
                    shrink-0
                    items-center
                    justify-center
                    rounded-full
                    text-sm
                    font-bold
                  "
                  :class="step.numberClass"
                >
                  <BaseIcon v-if="step.done" name="CheckIcon" class="h-5 w-5" />
                  <span v-else>{{ step.number }}</span>
                </span>
                <div>
                  <div class="font-semibold text-slate-900">
                    {{ step.title }}
                  </div>
                  <div class="text-xs text-slate-500">{{ step.subtitle }}</div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="showGuide" class="mt-6 rounded-2xl bg-slate-50 p-5 md:p-6">
            <h3 class="font-semibold text-slate-900">
              Ce que vous allez faire
            </h3>
            <ol class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-3">
              <li class="rounded-xl bg-white p-4 shadow-sm">
                <strong class="block text-slate-900"
                  >1. Compte personnel</strong
                >
                Créez votre compte directement sur le site officiel SUPER PDP et
                confirmez votre adresse e-mail.
              </li>
              <li class="rounded-xl bg-white p-4 shadow-sm">
                <strong class="block text-slate-900">2. Entreprise</strong>
                Ajoutez votre entreprise et effectuez la vérification du
                représentant légal exclusivement chez SUPER PDP.
              </li>
              <li class="rounded-xl bg-white p-4 shadow-sm">
                <strong class="block text-slate-900">3. Liaison</strong>
                Créez l’application AutoFacture dans SUPER PDP, puis copiez les
                deux codes de liaison dans l’écran sécurisé ci-dessous.
              </li>
            </ol>
          </div>
        </section>

        <div
          class="
            grid
            gap-6
            xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.85fr)]
          "
        >
          <main class="space-y-5">
            <section
              class="rounded-3xl border bg-white p-5 shadow-sm md:p-7"
              :class="
                stepOneActive
                  ? 'border-blue-300 ring-4 ring-blue-50'
                  : 'border-slate-200'
              "
            >
              <div
                class="
                  flex flex-col
                  gap-5
                  md:flex-row md:items-start md:justify-between
                "
              >
                <div class="flex gap-4">
                  <div
                    class="
                      flex
                      h-12
                      w-12
                      shrink-0
                      items-center
                      justify-center
                      rounded-2xl
                      bg-blue-100
                      text-blue-700
                    "
                  >
                    <BaseIcon name="UserAddIcon" class="h-6 w-6" />
                  </div>
                  <div>
                    <div
                      class="
                        text-xs
                        font-semibold
                        uppercase
                        tracking-wider
                        text-blue-600
                      "
                    >
                      Étape 1
                    </div>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">
                      Créer votre compte SUPER PDP
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                      L’inscription et la confirmation d’identité se font sur le
                      site de SUPER PDP. AutoFacture ne voit jamais votre mot de
                      passe ni vos justificatifs.
                    </p>
                  </div>
                </div>

                <span
                  v-if="store.connection.account_created"
                  class="
                    inline-flex
                    items-center
                    gap-2
                    rounded-full
                    bg-emerald-100
                    px-3
                    py-1.5
                    text-xs
                    font-semibold
                    text-emerald-700
                  "
                >
                  <BaseIcon name="CheckCircleIcon" class="h-4 w-4" /> Terminé
                </span>
              </div>

              <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <button
                  type="button"
                  class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    bg-blue-600
                    px-5
                    py-3
                    text-sm
                    font-semibold
                    text-white
                    shadow-lg shadow-blue-600/20
                    transition
                    hover:bg-blue-700
                  "
                  @click="openProvider"
                >
                  Ouvrir SUPER PDP
                  <BaseIcon name="ExternalLinkIcon" class="h-4 w-4" />
                </button>
                <button
                  v-if="!store.connection.account_created"
                  type="button"
                  class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    border border-slate-300
                    px-5
                    py-3
                    text-sm
                    font-semibold
                    text-slate-700
                    transition
                    hover:bg-slate-50
                  "
                  :disabled="store.isSaving || !isOwner"
                  @click="completeAccountStep"
                >
                  Mon compte est créé
                </button>
              </div>
            </section>

            <section
              class="rounded-3xl border bg-white p-5 shadow-sm md:p-7"
              :class="
                stepTwoActive
                  ? 'border-blue-300 ring-4 ring-blue-50'
                  : 'border-slate-200'
              "
            >
              <div
                class="
                  flex flex-col
                  gap-5
                  md:flex-row md:items-start md:justify-between
                "
              >
                <div class="flex gap-4">
                  <div
                    class="
                      flex
                      h-12
                      w-12
                      shrink-0
                      items-center
                      justify-center
                      rounded-2xl
                      bg-violet-100
                      text-violet-700
                    "
                  >
                    <BaseIcon name="OfficeBuildingIcon" class="h-6 w-6" />
                  </div>
                  <div>
                    <div
                      class="
                        text-xs
                        font-semibold
                        uppercase
                        tracking-wider
                        text-violet-600
                      "
                    >
                      Étape 2
                    </div>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">
                      Ajouter et vérifier votre entreprise
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                      Utilisez exactement les informations déjà enregistrées
                      dans AutoFacture.
                    </p>
                  </div>
                </div>

                <span
                  v-if="store.connection.company_verified"
                  class="
                    inline-flex
                    items-center
                    gap-2
                    rounded-full
                    bg-emerald-100
                    px-3
                    py-1.5
                    text-xs
                    font-semibold
                    text-emerald-700
                  "
                >
                  <BaseIcon name="CheckCircleIcon" class="h-4 w-4" /> Terminé
                </span>
              </div>

              <div
                class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5"
              >
                <div class="font-bold text-slate-900">
                  {{ store.company.name }}
                </div>
                <div
                  class="mt-2 grid gap-2 text-sm text-slate-600 sm:grid-cols-2"
                >
                  <div>
                    <span class="font-medium text-slate-800">SIREN :</span>
                    {{ store.company.siren || 'À renseigner' }}
                  </div>
                  <div>
                    <span class="font-medium text-slate-800">SIRET :</span>
                    {{ store.company.siret || 'À renseigner' }}
                  </div>
                  <div class="sm:col-span-2">
                    <span class="font-medium text-slate-800">Adresse :</span>
                    {{ companyAddress }}
                  </div>
                </div>
              </div>

              <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <button
                  type="button"
                  class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    bg-violet-600
                    px-5
                    py-3
                    text-sm
                    font-semibold
                    text-white
                    shadow-lg shadow-violet-600/20
                    transition
                    hover:bg-violet-700
                  "
                  @click="openProvider"
                >
                  Ajouter mon entreprise sur SUPER PDP
                  <BaseIcon name="ExternalLinkIcon" class="h-4 w-4" />
                </button>
                <button
                  v-if="!store.connection.company_verified"
                  type="button"
                  class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    border border-slate-300
                    px-5
                    py-3
                    text-sm
                    font-semibold
                    text-slate-700
                    transition
                    hover:bg-slate-50
                  "
                  :disabled="
                    store.isSaving ||
                    !store.connection.account_created ||
                    !isOwner
                  "
                  @click="completeCompanyStep"
                >
                  Mon entreprise est vérifiée
                </button>
              </div>
            </section>

            <section
              class="rounded-3xl border bg-white p-5 shadow-sm md:p-7"
              :class="
                stepThreeActive
                  ? 'border-blue-300 ring-4 ring-blue-50'
                  : 'border-slate-200'
              "
            >
              <div
                class="
                  flex flex-col
                  gap-5
                  md:flex-row md:items-start md:justify-between
                "
              >
                <div class="flex gap-4">
                  <div
                    class="
                      flex
                      h-12
                      w-12
                      shrink-0
                      items-center
                      justify-center
                      rounded-2xl
                      bg-cyan-100
                      text-cyan-700
                    "
                  >
                    <BaseIcon name="LinkIcon" class="h-6 w-6" />
                  </div>
                  <div>
                    <div
                      class="
                        text-xs
                        font-semibold
                        uppercase
                        tracking-wider
                        text-cyan-700
                      "
                    >
                      Étape 3
                    </div>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">
                      Relier AutoFacture à votre entreprise
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                      Dans SUPER PDP, créez une application confidentielle pour
                      votre entreprise, choisissez Factur-X et activez la
                      souscription. Revenez ensuite saisir les deux codes de
                      liaison.
                    </p>
                  </div>
                </div>

                <span
                  v-if="store.connection.has_credentials"
                  class="
                    inline-flex
                    items-center
                    gap-2
                    rounded-full
                    bg-emerald-100
                    px-3
                    py-1.5
                    text-xs
                    font-semibold
                    text-emerald-700
                  "
                >
                  <BaseIcon name="ShieldCheckIcon" class="h-4 w-4" /> Codes
                  chiffrés
                </span>
              </div>

              <div
                class="
                  mt-6
                  rounded-2xl
                  border border-amber-200
                  bg-amber-50
                  p-4
                  text-sm
                  leading-6
                  text-amber-900
                "
              >
                <strong>À retenir :</strong> sélectionnez la bonne entreprise et
                le bon environnement. Les codes de production et de bac à sable
                sont différents.
              </div>

              <form class="mt-6 grid gap-5" @submit.prevent="saveCredentials">
                <div class="grid gap-5 md:grid-cols-2">
                  <label class="block">
                    <span class="text-sm font-semibold text-slate-800"
                      >Environnement</span
                    >
                    <select
                      v-model="form.environment"
                      class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                        text-sm
                        focus:border-blue-500 focus:ring-blue-500
                      "
                      :disabled="!isOwner"
                    >
                      <option value="sandbox">
                        Bac à sable — essais sans valeur légale
                      </option>
                      <option value="production">
                        Production — factures réelles
                      </option>
                    </select>
                  </label>

                  <div
                    class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600"
                  >
                    <div class="font-semibold text-slate-800">
                      Code actuellement enregistré
                    </div>
                    <div class="mt-2 font-mono text-xs">
                      {{
                        store.connection.masked_client_id ||
                        'Aucun code enregistré'
                      }}
                    </div>
                  </div>
                </div>

                <label class="block">
                  <span class="text-sm font-semibold text-slate-800"
                    >Identifiant de liaison</span
                  >
                  <input
                    v-model.trim="form.client_id"
                    type="text"
                    autocomplete="off"
                    class="
                      mt-2
                      w-full
                      rounded-xl
                      border-slate-300
                      text-sm
                      focus:border-blue-500 focus:ring-blue-500
                    "
                    placeholder="Collez l’identifiant fourni par SUPER PDP"
                    :disabled="!isOwner"
                  />
                </label>

                <label class="block">
                  <span class="text-sm font-semibold text-slate-800"
                    >Clé secrète</span
                  >
                  <div class="relative mt-2">
                    <input
                      v-model="form.client_secret"
                      :type="showSecret ? 'text' : 'password'"
                      autocomplete="new-password"
                      class="
                        w-full
                        rounded-xl
                        border-slate-300
                        pr-28
                        text-sm
                        focus:border-blue-500 focus:ring-blue-500
                      "
                      placeholder="Collez la clé secrète fournie par SUPER PDP"
                      :disabled="!isOwner"
                    />
                    <button
                      type="button"
                      class="
                        absolute
                        inset-y-0
                        right-3
                        text-xs
                        font-semibold
                        text-blue-700
                      "
                      @click="showSecret = !showSecret"
                    >
                      {{ showSecret ? 'Masquer' : 'Afficher' }}
                    </button>
                  </div>
                </label>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                  <button
                    type="submit"
                    class="
                      inline-flex
                      items-center
                      justify-center
                      gap-2
                      rounded-xl
                      bg-gradient-to-r
                      from-blue-600
                      to-violet-600
                      px-6
                      py-3
                      text-sm
                      font-semibold
                      text-white
                      shadow-lg shadow-blue-600/20
                      transition
                      hover:brightness-110
                      disabled:cursor-not-allowed disabled:opacity-50
                    "
                    :disabled="!canSaveCredentials"
                  >
                    <BaseIcon name="LockClosedIcon" class="h-4 w-4" />
                    Chiffrer et enregistrer les codes
                  </button>
                  <span class="text-xs leading-5 text-slate-500">
                    Après enregistrement, la clé secrète n’est plus renvoyée au
                    navigateur.
                  </span>
                </div>
              </form>
            </section>
          </main>

          <aside class="space-y-5">
            <section
              class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
              <div class="flex items-center gap-3">
                <div
                  class="
                    flex
                    h-11
                    w-11
                    items-center
                    justify-center
                    rounded-2xl
                    bg-emerald-100
                    text-emerald-700
                  "
                >
                  <BaseIcon name="ShieldCheckIcon" class="h-6 w-6" />
                </div>
                <div>
                  <h2 class="font-bold text-slate-900">Confidentialité</h2>
                  <p class="text-xs text-slate-500">
                    Séparation claire des responsabilités
                  </p>
                </div>
              </div>

              <ul class="mt-5 space-y-3 text-sm leading-6 text-slate-600">
                <li class="flex gap-3">
                  <BaseIcon
                    name="CheckIcon"
                    class="mt-1 h-4 w-4 shrink-0 text-emerald-600"
                  />
                  Les pièces d’identité restent chez SUPER PDP.
                </li>
                <li class="flex gap-3">
                  <BaseIcon
                    name="CheckIcon"
                    class="mt-1 h-4 w-4 shrink-0 text-emerald-600"
                  />
                  Les codes sont chiffrés dans la base AutoFacture.
                </li>
                <li class="flex gap-3">
                  <BaseIcon
                    name="CheckIcon"
                    class="mt-1 h-4 w-4 shrink-0 text-emerald-600"
                  />
                  La clé secrète n’apparaît ni dans l’interface ni dans les
                  journaux.
                </li>
                <li class="flex gap-3">
                  <BaseIcon
                    name="CheckIcon"
                    class="mt-1 h-4 w-4 shrink-0 text-emerald-600"
                  />
                  Chaque entreprise conserve son propre compte plateforme.
                </li>
              </ul>
            </section>

            <section
              class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
              <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-slate-900">Connexion technique</h2>
                <span
                  class="h-3 w-3 rounded-full"
                  :class="connectionDotClass"
                />
              </div>

              <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                  <dt class="text-slate-500">Plateforme</dt>
                  <dd class="font-semibold text-slate-900">SUPER PDP</dd>
                </div>
                <div class="flex justify-between gap-4">
                  <dt class="text-slate-500">Environnement</dt>
                  <dd class="font-semibold text-slate-900">
                    {{ environmentLabel }}
                  </dd>
                </div>
                <div class="flex justify-between gap-4">
                  <dt class="text-slate-500">Codes</dt>
                  <dd class="font-semibold text-slate-900">
                    {{
                      store.connection.has_credentials
                        ? 'Enregistrés'
                        : 'Absents'
                    }}
                  </dd>
                </div>
                <div class="flex justify-between gap-4">
                  <dt class="text-slate-500">Dernier test</dt>
                  <dd class="text-right font-semibold text-slate-900">
                    {{ lastTestLabel }}
                  </dd>
                </div>
              </dl>

              <div
                v-if="store.lastTest"
                class="mt-5 rounded-xl p-4 text-sm leading-6"
                :class="
                  store.lastTest.successful
                    ? 'bg-emerald-50 text-emerald-800'
                    : 'bg-amber-50 text-amber-900'
                "
              >
                {{ store.lastTest.message }}
              </div>

              <div class="mt-5 grid gap-3">
                <div
                  v-if="
                    store.connection.has_credentials &&
                    !store.connection.oauth_ready
                  "
                  class="
                    rounded-xl
                    bg-amber-50
                    p-3
                    text-xs
                    leading-5
                    text-amber-900
                  "
                >
                  La liaison OAuth reste désactivée tant que les URL officielles
                  SUPER PDP ne sont pas configurées sur le serveur.
                </div>
                <button
                  v-if="store.connection.connection_status !== 'connected'"
                  type="button"
                  class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    bg-gradient-to-r
                    from-blue-600
                    to-violet-600
                    px-4
                    py-3
                    text-sm
                    font-semibold
                    text-white
                    transition
                    hover:brightness-110
                    disabled:opacity-50
                  "
                  :disabled="
                    !store.connection.has_credentials ||
                    !store.connection.oauth_ready ||
                    store.isSaving ||
                    !isOwner
                  "
                  @click="authorizeConnection"
                >
                  <BaseIcon name="ExternalLinkIcon" class="h-4 w-4" />
                  Autoriser depuis SUPER PDP
                </button>
                <button
                  type="button"
                  class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    bg-slate-900
                    px-4
                    py-3
                    text-sm
                    font-semibold
                    text-white
                    transition
                    hover:bg-slate-800
                    disabled:opacity-50
                  "
                  :disabled="
                    !store.connection.has_credentials ||
                    store.isSaving ||
                    !isOwner
                  "
                  @click="testConnection"
                >
                  <BaseIcon name="StatusOnlineIcon" class="h-4 w-4" />
                  Tester la connexion
                </button>
                <button
                  type="button"
                  class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    border border-slate-300
                    px-4
                    py-3
                    text-sm
                    font-semibold
                    text-slate-700
                    transition
                    hover:bg-slate-50
                  "
                  @click="openDocumentation"
                >
                  Consulter la documentation officielle
                </button>
                <button
                  v-if="store.connection.has_credentials"
                  type="button"
                  class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    px-4
                    py-2
                    text-xs
                    font-semibold
                    text-red-600
                    transition
                    hover:bg-red-50
                  "
                  :disabled="store.isSaving || !isOwner"
                  @click="disconnect"
                >
                  Remplacer ou supprimer la liaison
                </button>
              </div>
            </section>

            <section
              v-if="!isOwner"
              class="
                rounded-3xl
                border border-amber-200
                bg-amber-50
                p-5
                text-sm
                leading-6
                text-amber-900
              "
            >
              Seul le propriétaire de l’entreprise peut enregistrer ou remplacer
              les codes de connexion.
            </section>
          </aside>
        </div>

        <section
          v-if="store.connection.connection_status === 'connected'"
          class="
            rounded-3xl
            border border-slate-200
            bg-white
            p-6
            shadow-sm
            md:p-8
          "
        >
          <div
            class="
              flex flex-col
              gap-2
              md:flex-row md:items-end md:justify-between
            "
          >
            <div>
              <h2 class="text-xl font-bold text-slate-900">
                Centre de facturation électronique
              </h2>
              <p class="mt-1 text-sm text-slate-500">
                La connexion est préparée. Les flux seront activés
                progressivement après validation du bac à sable.
              </p>
            </div>
            <span
              class="
                rounded-full
                bg-blue-50
                px-3
                py-1.5
                text-xs
                font-semibold
                text-blue-700
              "
              >Bêta technique</span
            >
          </div>

          <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
              v-for="module in modules"
              :key="module.title"
              class="rounded-2xl border border-slate-200 p-5"
            >
              <div
                class="flex h-11 w-11 items-center justify-center rounded-2xl"
                :class="module.iconClass"
              >
                <BaseIcon :name="module.icon" class="h-5 w-5" />
              </div>
              <h3 class="mt-4 font-bold text-slate-900">{{ module.title }}</h3>
              <p class="mt-2 text-sm leading-6 text-slate-500">
                {{ module.description }}
              </p>
              <div
                class="
                  mt-4
                  text-xs
                  font-semibold
                  uppercase
                  tracking-wider
                  text-slate-400
                "
              >
                Prochain incrément
              </div>
            </div>
          </div>
        </section>
      </template>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useElectronicInvoicingStore } from '@/scripts/admin/stores/electronic-invoicing'
import { useUserStore } from '@/scripts/admin/stores/user'

const store = useElectronicInvoicingStore()
const companyStore = useCompanyStore()
const userStore = useUserStore()
const showGuide = ref(false)
const showSecret = ref(false)

const form = reactive({
  environment: 'sandbox',
  client_id: '',
  client_secret: '',
})

const modules = [
  {
    title: 'Ventes électroniques',
    description: 'Transmettre les factures et suivre leur cycle de vie.',
    icon: 'UploadIcon',
    iconClass: 'bg-blue-100 text-blue-700',
  },
  {
    title: 'Achats électroniques',
    description: 'Recevoir, vérifier et rapprocher les factures fournisseurs.',
    icon: 'DownloadIcon',
    iconClass: 'bg-emerald-100 text-emerald-700',
  },
  {
    title: 'Anomalies',
    description: 'Identifier les rejets, doublons et données à corriger.',
    icon: 'ExclamationIcon',
    iconClass: 'bg-amber-100 text-amber-700',
  },
  {
    title: 'Journal de preuve',
    description: 'Conserver les statuts, empreintes et accusés techniques.',
    icon: 'ClipboardListIcon',
    iconClass: 'bg-violet-100 text-violet-700',
  },
]

const isOwner = computed(() => {
  return (
    Number(companyStore.selectedCompany?.owner_id) ===
    Number(userStore.currentUser?.id)
  )
})

const steps = computed(() => [
  {
    number: 1,
    title: 'Créer le compte',
    subtitle: store.connection.account_created
      ? 'Compte confirmé'
      : 'Sur le site SUPER PDP',
    done: store.connection.account_created,
    cardClass: store.connection.account_created
      ? 'border-emerald-200 bg-emerald-50'
      : 'border-blue-200 bg-blue-50',
    numberClass: store.connection.account_created
      ? 'bg-emerald-600 text-white'
      : 'bg-blue-600 text-white',
  },
  {
    number: 2,
    title: 'Vérifier l’entreprise',
    subtitle: store.connection.company_verified
      ? 'Entreprise confirmée'
      : 'Identité et mandat',
    done: store.connection.company_verified,
    cardClass: store.connection.company_verified
      ? 'border-emerald-200 bg-emerald-50'
      : 'border-slate-200 bg-white',
    numberClass: store.connection.company_verified
      ? 'bg-emerald-600 text-white'
      : 'bg-slate-200 text-slate-600',
  },
  {
    number: 3,
    title: 'Relier AutoFacture',
    subtitle: store.connection.has_credentials
      ? 'Codes chiffrés'
      : 'Connexion technique',
    done: store.connection.has_credentials,
    cardClass: store.connection.has_credentials
      ? 'border-emerald-200 bg-emerald-50'
      : 'border-slate-200 bg-white',
    numberClass: store.connection.has_credentials
      ? 'bg-emerald-600 text-white'
      : 'bg-slate-200 text-slate-600',
  },
])

const stepOneActive = computed(() => !store.connection.account_created)
const stepTwoActive = computed(
  () => store.connection.account_created && !store.connection.company_verified
)
const stepThreeActive = computed(
  () => store.connection.company_verified && !store.connection.has_credentials
)

const statusLabel = computed(() => {
  const labels = {
    not_configured: 'À configurer',
    credentials_saved: 'Codes enregistrés',
    authorization_pending: 'Autorisation en cours',
    connected: 'Connecté',
    token_refresh_required: 'Réautorisation requise',
    connection_lost: 'Connexion perdue',
  }

  return labels[store.connection?.connection_status] || 'À configurer'
})

const statusBadgeClass = computed(() => {
  if (store.connection?.connection_status === 'connected')
    return 'bg-emerald-400/20 text-emerald-100'
  if (
    ['connection_lost', 'token_refresh_required'].includes(
      store.connection?.connection_status
    )
  )
    return 'bg-red-400/20 text-red-100'
  if (
    ['credentials_saved', 'authorization_pending'].includes(
      store.connection?.connection_status
    )
  )
    return 'bg-cyan-400/20 text-cyan-100'

  return 'bg-white/15 text-white'
})

const connectionDotClass = computed(() => {
  if (store.connection?.connection_status === 'connected')
    return 'bg-emerald-500 shadow-[0_0_14px_rgba(16,185,129,0.7)]'
  if (
    ['connection_lost', 'token_refresh_required'].includes(
      store.connection?.connection_status
    )
  )
    return 'bg-red-500'
  if (store.connection?.has_credentials) return 'bg-amber-400'

  return 'bg-slate-300'
})

const environmentLabel = computed(() => {
  return store.connection?.environment === 'production'
    ? 'Production'
    : 'Bac à sable'
})

const companyAddress = computed(() => {
  const address = store.company?.address
  if (!address) return 'À renseigner dans les paramètres de l’entreprise'

  return [
    address.address_street_1,
    address.address_street_2,
    address.zip,
    address.city,
  ]
    .filter(Boolean)
    .join(', ')
})

const lastTestLabel = computed(() => {
  if (!store.connection?.last_tested_at) return 'Jamais'

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(store.connection.last_tested_at))
})

const canSaveCredentials = computed(() => {
  return (
    isOwner.value &&
    store.connection.company_verified &&
    form.client_id.length >= 4 &&
    form.client_secret.length >= 8 &&
    !store.isSaving
  )
})

onMounted(async () => {
  await store.load()
  form.environment = store.connection.environment || 'sandbox'
})

function openProvider() {
  window.open(store.links.portal, '_blank', 'noopener,noreferrer')
}

function openDocumentation() {
  window.open(store.links.documentation, '_blank', 'noopener,noreferrer')
}

async function completeAccountStep() {
  await store.updateProgress({ account_created: true })
}

async function completeCompanyStep() {
  await store.updateProgress({ company_verified: true })
}

async function saveCredentials() {
  if (!canSaveCredentials.value) return

  await store.saveCredentials({ ...form })
  form.client_id = ''
  form.client_secret = ''
  showSecret.value = false
}

async function testConnection() {
  await store.testConnection()
}

async function authorizeConnection() {
  const authorizationUrl = await store.startOAuth()

  if (authorizationUrl) {
    window.location.assign(authorizationUrl)
  }
}

async function disconnect() {
  const confirmed = window.confirm(
    'Supprimer les codes de liaison SUPER PDP enregistrés pour cette entreprise ?'
  )

  if (confirmed) {
    await store.disconnect()
  }
}
</script>
