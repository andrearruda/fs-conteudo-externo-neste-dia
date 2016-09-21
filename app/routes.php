<?php
// Routes

$app->get('/', function(){
    return $this->response->withRedirect($this->router->pathFor('ephemeris.monthly'));
});

$app->group('/ephemeris', function () {
    $this->get('/monthly[/{month}]', App\Action\Ephemeris\MonthlyAction::class)->setName('ephemeris.monthly');
    $this->get('/daily/{day}-{month}', App\Action\Ephemeris\DailyAction::class)->setName('ephemeris.daily');
});
