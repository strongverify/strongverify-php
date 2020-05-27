strongverify-php
================

This is a stand-alone php SDK for strongverify.com. It is a PSR-4 compliant PHP package installed using composer.

Installation
------------

The package is installed using composer.

```
composer require strongverify/strongverify-php
```

You will need to set environment variables required for authentication. These will be available on your StringVerify dashboard or you may get the from StrongVerify support. Not even the test of this package will run without this as it runs against a live test environment. 

```bash
export AUTH0_DOMAIN=__the_sv_auth_domain__
export AUTH0_CLIENT_ID=__your_sv_client_id__
export AUTH0_CLIENT_SECRET=__your_sv_client_secret__
export SV_API_BASE_URI=__the_sv_base_api_uri__
```

Tests
-----

We provide unit tests as part of the package. This may be used to verify successful installation. From the package install directory (usually something like `vendor/strongverify/strongverify-php`)  you may run:
```
vendor/bin/phpunit ./tests
```
If you have the env var `SV_WRITE_RESULTS` set to `1`, the test will write out their results in `tests/results` where you can inspect raw results.


