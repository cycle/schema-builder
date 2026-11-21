# Changelog

## [2.13.0](https://github.com/cycle/schema-builder/compare/2.12.2...2.13.0) (2026-11-21)


### Features

* require PHP 8.2 and polish the tooling ([#90](https://github.com/cycle/schema-builder/issues/90)) ([b1b3a66](https://github.com/cycle/schema-builder/commit/b1b3a66b594aacd3e9dbfd657148ad50ee77f294))


### Documentation

* add the mutation testing badge ([2af09a1](https://github.com/cycle/schema-builder/commit/2af09a1cb88174b8979d508f2bd2afeae414f97e))
* move the Discord badge to the first row ([#92](https://github.com/cycle/schema-builder/issues/92)) ([6511b6e](https://github.com/cycle/schema-builder/commit/6511b6e9397d355d81aef2547e280a7c26440b90))
* restructure the README ([#91](https://github.com/cycle/schema-builder/issues/91)) ([807c86a](https://github.com/cycle/schema-builder/commit/807c86a38b128d82efc7fcc2240532b458cae468))


### Dependencies

* raise the minimum versions of the cycle packages ([#103](https://github.com/cycle/schema-builder/issues/103)) ([c2128d7](https://github.com/cycle/schema-builder/commit/c2128d756c3757eaa92b7282593631c3ee55090a))


### Tests

* **compiler:** compile schema modifiers and table inheritance against a database ([0f2b844](https://github.com/cycle/schema-builder/commit/0f2b8442ad0de5c5ac0bd16676f62a4e00376068))
* convert PHPUnit tests to Testo with Rector ([44bfae3](https://github.com/cycle/schema-builder/commit/44bfae367eb40a9e0a6b7311f50791a157155d6c))
* cover batched table schema loading across databases ([bcc0f7c](https://github.com/cycle/schema-builder/commit/bcc0f7c155f5fb81060dd7f2edca232bc77fac2f))
* cover default classes, column conflicts and referenced keys of the compiler ([bcc0f7c](https://github.com/cycle/schema-builder/commit/bcc0f7c155f5fb81060dd7f2edca232bc77fac2f))
* cover entity cloning, merging and primary key checks ([bcc0f7c](https://github.com/cycle/schema-builder/commit/bcc0f7c155f5fb81060dd7f2edca232bc77fac2f))
* cover relation key normalization, indexes and index lookup ([bcc0f7c](https://github.com/cycle/schema-builder/commit/bcc0f7c155f5fb81060dd7f2edca232bc77fac2f))
* cover the output of PrintChanges for dropped, unchanged and several tables ([bcc0f7c](https://github.com/cycle/schema-builder/commit/bcc0f7c155f5fb81060dd7f2edca232bc77fac2f))
* cover type options, defaults and cast defaults of rendered columns ([bcc0f7c](https://github.com/cycle/schema-builder/commit/bcc0f7c155f5fb81060dd7f2edca232bc77fac2f))
* **exception:** cover the embedded primary key collision explanation ([e7a39b4](https://github.com/cycle/schema-builder/commit/e7a39b431e0c7a37beabeb275d25b385fa4db547))
* **generator:** cover resetting, syncing, validating, typecasting and resolving edge cases ([e7a39b4](https://github.com/cycle/schema-builder/commit/e7a39b431e0c7a37beabeb275d25b385fa4db547))
* **generator:** run the typecast, sync, print and render generators in the Unit suite ([0f2b844](https://github.com/cycle/schema-builder/commit/0f2b8442ad0de5c5ac0bd16676f62a4e00376068))
* kill escaped mutants ([#98](https://github.com/cycle/schema-builder/issues/98)) ([bcc0f7c](https://github.com/cycle/schema-builder/commit/bcc0f7c155f5fb81060dd7f2edca232bc77fac2f))
* move the test suites up to tests/ ([44bfae3](https://github.com/cycle/schema-builder/commit/44bfae367eb40a9e0a6b7311f50791a157155d6c))
* raise line coverage ([#101](https://github.com/cycle/schema-builder/issues/101)) ([0f2b844](https://github.com/cycle/schema-builder/commit/0f2b8442ad0de5c5ac0bd16676f62a4e00376068))
* raise line coverage ([#99](https://github.com/cycle/schema-builder/issues/99)) ([e7a39b4](https://github.com/cycle/schema-builder/commit/e7a39b431e0c7a37beabeb275d25b385fa4db547))
* **registry:** reject relation lookups on an unregistered entity ([0f2b844](https://github.com/cycle/schema-builder/commit/0f2b8442ad0de5c5ac0bd16676f62a4e00376068))
* **relation:** cover single-field and cross-database foreign keys ([e7a39b4](https://github.com/cycle/schema-builder/commit/e7a39b431e0c7a37beabeb275d25b385fa4db547))
* **relation:** generate every relation type in the Unit suite ([0f2b844](https://github.com/cycle/schema-builder/commit/0f2b8442ad0de5c5ac0bd16676f62a4e00376068))
* run schema-builder tests on the cycle/testing harness ([44bfae3](https://github.com/cycle/schema-builder/commit/44bfae367eb40a9e0a6b7311f50791a157155d6c))
* run the tests on Testo with cycle/testing ([#95](https://github.com/cycle/schema-builder/issues/95)) ([44bfae3](https://github.com/cycle/schema-builder/commit/44bfae367eb40a9e0a6b7311f50791a157155d6c))
* split Testo suites into Feature and Regression ([44bfae3](https://github.com/cycle/schema-builder/commit/44bfae367eb40a9e0a6b7311f50791a157155d6c))
* wire cycle/testing and Testo ([44bfae3](https://github.com/cycle/schema-builder/commit/44bfae367eb40a9e0a6b7311f50791a157155d6c))


### Continuous Integration

* collect coverage from the unit tests too ([44bfae3](https://github.com/cycle/schema-builder/commit/44bfae367eb40a9e0a6b7311f50791a157155d6c))
* pin the shared cycle/gh-actions workflows to v4.2.0 ([#100](https://github.com/cycle/schema-builder/issues/100)) ([b53f16e](https://github.com/cycle/schema-builder/commit/b53f16e8f249daa6643a9a59d67e98f33bf13bd9))
* raise the mutation score gate ([bcc0f7c](https://github.com/cycle/schema-builder/commit/bcc0f7c155f5fb81060dd7f2edca232bc77fac2f))
* run mutation testing through the shared cycle/gh-actions workflow ([2af09a1](https://github.com/cycle/schema-builder/commit/2af09a1cb88174b8979d508f2bd2afeae414f97e))
* run mutation testing with Infection ([#97](https://github.com/cycle/schema-builder/issues/97)) ([2af09a1](https://github.com/cycle/schema-builder/commit/2af09a1cb88174b8979d508f2bd2afeae414f97e))
* run tests through the shared cycle/gh-actions workflow ([44bfae3](https://github.com/cycle/schema-builder/commit/44bfae367eb40a9e0a6b7311f50791a157155d6c))
* show the test job as Testo ([44bfae3](https://github.com/cycle/schema-builder/commit/44bfae367eb40a9e0a6b7311f50791a157155d6c))

## [2.12.2](https://github.com/cycle/schema-builder/compare/2.12.1...2.12.2) (2026-10-05)


### Bug Fixes

* Make relation-generated FK nullable when any relation sharing it asks for nullable ([#86](https://github.com/cycle/schema-builder/issues/86)) ([e97646f](https://github.com/cycle/schema-builder/commit/e97646f9255d2c3fe36a34782b5e462f3a126a64))

## [2.12.1](https://github.com/cycle/schema-builder/compare/v2.12.0...2.12.1) (2026-08-19)


### Performance Improvements

* Load table schemas lazily and in bulk in Registry ([#83](https://github.com/cycle/schema-builder/issues/83)) ([884cd3a](https://github.com/cycle/schema-builder/commit/884cd3a73d164e895a58d1b9dd4970b1a68fa412))


### Continuous Integration

* Set up release-please for automated releases ([c2f9a1e](https://github.com/cycle/schema-builder/commit/c2f9a1e51d6cd166439723c53ca11a3f6ded89e9))
