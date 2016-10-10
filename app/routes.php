<?php
// Routes

$app->get('/', function(){
    return $this->response->withRedirect($this->router->pathFor('ephemeris.yearly'));
});

$app->group('/ephemeris', function () {
    $this->get('/yearly', App\Action\Ephemeris\YearlyAction::class)->setName('ephemeris.yearly');
    $this->get('/monthly[/{month}]', App\Action\Ephemeris\MonthlyAction::class)->setName('ephemeris.monthly');
    $this->get('/daily/{day}-{month}[/]', App\Action\Ephemeris\DailyAction::class)->setName('ephemeris.daily');
});
