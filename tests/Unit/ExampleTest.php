<?php

test('it converts a calendar name to uppercase', function () {
    $calendarName = str('calendar')->upper()->toString();

    expect($calendarName)->toBe('CALENDAR');
});
