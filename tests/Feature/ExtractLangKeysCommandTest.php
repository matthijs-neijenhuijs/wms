<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->fixturePath = base_path('app/Console/Commands/TempTranslationFixture.php');
    $this->localeFile = lang_path('test-extract.json');

    File::delete($this->fixturePath);
    File::delete($this->localeFile);
});

afterEach(function () {
    File::delete($this->fixturePath);
    File::delete($this->localeFile);
});
it('extracts translation keys into a locale json file', function () {
    File::put($this->fixturePath, <<<'PHP'
<?php

declare(strict_types=1);

__('user.name');
__('user.email');
@lang('orders.created');
PHP);

    artisan('lang:extract test-extract')->assertSuccessful();

    $translations = json_decode(File::get($this->localeFile), true, 512, JSON_THROW_ON_ERROR);

    expect($translations)->toMatchArray([
        'orders.created' => '',
        'user.email' => '',
        'user.name' => '',
    ]);
});
