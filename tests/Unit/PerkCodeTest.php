<?php

use App\Domain\Rewards\PerkCode;

it('reads back the codes it made and nothing else', function () {
    $codes = new PerkCode('secret-key');
    $code = $codes->for(42, 'highlander_gift_card');

    expect($code)->toStartWith('PERK-42-highlander_gift_card-')
        ->and($codes->parse($code))->toBe(['hiker_id' => 42, 'reward' => 'highlander_gift_card'])
        ->and($codes->parse(" {$code}\n"))->not->toBeNull()
        ->and($codes->parse(str_replace('PERK-42-', 'PERK-43-', $code)))->toBeNull()
        ->and($codes->parse(str_replace('gift_card', 'winter_bonus', $code)))->toBeNull()
        ->and((new PerkCode('other-key'))->parse($code))->toBeNull()
        ->and($codes->parse('HK-7F3K2Q'))->toBeNull();
});
