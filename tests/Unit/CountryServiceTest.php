<?php

declare(strict_types=1);

use Awalhadi\Addressable\Services\CountryService;
use Awalhadi\Addressable\Support\Country;

beforeEach(function () {
    $this->countryService = new CountryService();
});

it('can get all countries', function () {
    $countries = $this->countryService->all();
    
    expect($countries)->toBeArray()
        ->and(count($countries))->toBeGreaterThan(0)
        ->and($countries)->toHaveKey('US')
        ->and($countries['US'])->toHaveKey('name');
});

it('can get country by code', function () {
    $country = $this->countryService->get('US');
    
    expect($country)->toBeArray()
        ->and($country['name'])->toBe('United States')
        ->and($country['code'])->toBe('US');
});

it('returns null for invalid country code', function () {
    $country = $this->countryService->get('XX');
    
    expect($country)->toBeNull();
});

it('can get country name', function () {
    $name = $this->countryService->getName('US');
    
    expect($name)->toBe('United States');
});

it('can check if country exists', function () {
    expect($this->countryService->exists('US'))->toBeTrue()
        ->and($this->countryService->exists('XX'))->toBeFalse();
});

it('can search countries by name', function () {
    $results = $this->countryService->search('United');
    
    expect($results)->toBeArray()
        ->and(count($results))->toBeGreaterThan(0);
    
    foreach ($results as $country) {
        expect(str_contains(strtolower($country['name']), 'united'))->toBeTrue();
    }
});

it('can get popular countries', function () {
    $popular = $this->countryService->getPopular();
    
    expect($popular)->toBeArray()
        ->and($popular)->toHaveKey('US')
        ->and($popular)->toHaveKey('GB');
});

it('can validate country codes', function () {
    expect($this->countryService->isValidCode('US'))->toBeTrue()
        ->and($this->countryService->isValidCode('us'))->toBeTrue()
        ->and($this->countryService->isValidCode('USA'))->toBeFalse()
        ->and($this->countryService->isValidCode('X'))->toBeFalse();
});

it('can get statistics', function () {
    $stats = $this->countryService->getStats();
    
    expect($stats)->toBeArray()
        ->and($stats)->toHaveKey('total_countries')
        ->and($stats)->toHaveKey('continents')
        ->and($stats['total_countries'])->toBeGreaterThan(0);
});

it('works with country helper function', function () {
    $country = country('US');
    
    expect($country)->toBeInstanceOf(Country::class)
        ->and($country->getName())->toBe('United States')
        ->and($country->getCode())->toBe('US')
        ->and((string) $country)->toBe('United States');
});

it('works with countries helper function', function () {
    $service = countries();
    
    expect($service)->toBeInstanceOf(CountryService::class)
        ->and($service->getName('US'))->toBe('United States');
});