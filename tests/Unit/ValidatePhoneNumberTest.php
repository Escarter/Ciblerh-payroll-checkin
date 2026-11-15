<?php

dataset('phones', [
    'commas' => ['698,240,964', '+237698240964'],
    'national' => ['698240964', '+237698240964'],
    'trunk zero' => ['0698240964', '+237698240964'],
    'spaced international' => ['+237 698 240 964', '+237698240964'],
    'code without plus' => ['237698240964', '+237698240964'],
    'duplicated code' => ['237237698240964', '+237698240964'],
    'stray zero after code' => ['+2370698240964', '+237698240964'],
    '00 prefix' => ['00237698240964', '+237698240964'],
    'us' => ['1-434-649-5073', '+14346495073'],
    'france' => ['+33 6 12 34 56 78', '+33612345678'],
]);

it('normalises phone numbers to E.164', function (string $input, string $expected) {
    $result = validatePhoneNumber($input);

    expect($result['valid'])->toBeTrue()->and($result['formatted'])->toBe($expected);
})->with('phones');

it('rejects unusable phone numbers', function (string $input) {
    expect(validatePhoneNumber($input)['valid'])->toBeFalse();
})->with(['', 'abc', '000', '12345', '+0123456789']);

it('honours an explicit default country code', function () {
    expect(validatePhoneNumber('612345678', '33')['formatted'])->toBe('+33612345678');
});
