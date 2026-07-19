<?php

use Crater\Http\Middleware\EnsureFeatureIsEnabled;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('laisse passer une fonction active', function () {
    config()->set('autofacture.features.expenses', true);

    $request = Request::create('/api/v1/expenses', 'GET');
    $request->headers->set('Accept', 'application/json');

    $response = app(EnsureFeatureIsEnabled::class)->handle(
        $request,
        fn () => response()->json(['ok' => true])
    );

    expect($response->getStatusCode())->toBe(Response::HTTP_OK)
        ->and($response->getData(true))->toMatchArray(['ok' => true]);
});

it('bloque une fonction api entièrement désactivée', function () {
    config()->set('autofacture.features.expenses', false);

    $request = Request::create('/api/v1/expenses', 'GET');
    $request->headers->set('Accept', 'application/json');

    $response = app(EnsureFeatureIsEnabled::class)->handle(
        $request,
        fn () => response()->json(['ok' => true])
    );

    expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND)
        ->and($response->getData(true))->toMatchArray([
            'code' => 'FEATURE_DISABLED',
            'feature' => 'expenses',
        ]);
});

it('redirige une page admin désactivée vers le tableau de bord', function () {
    config()->set('autofacture.features.reports', false);

    $request = Request::create('/admin/reports', 'GET');

    $response = app(EnsureFeatureIsEnabled::class)->handle(
        $request,
        fn () => response('ok')
    );

    expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
        ->and($response->headers->get('Location'))->toEndWith('/admin/dashboard');
});

it('laisse lire les rôles nécessaires à l’écran utilisateurs', function () {
    config()->set('autofacture.settings_features.roles', false);

    $request = Request::create('/api/v1/roles', 'GET');
    $request->headers->set('Accept', 'application/json');

    $response = app(EnsureFeatureIsEnabled::class)->handle(
        $request,
        fn () => response()->json(['ok' => true])
    );

    expect($response->getStatusCode())->toBe(Response::HTTP_OK);
});

it('bloque l’écriture des rôles avancés désactivés', function () {
    config()->set('autofacture.settings_features.roles', false);

    $request = Request::create('/api/v1/roles', 'POST');
    $request->headers->set('Accept', 'application/json');

    $response = app(EnsureFeatureIsEnabled::class)->handle(
        $request,
        fn () => response()->json(['ok' => true])
    );

    expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND)
        ->and($response->getData(true)['feature'])->toBe('roles');
});
