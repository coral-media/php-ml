# Contributing to PHP-ML

PHP-ML is an open source project. If you'd like to contribute, please read the following text. Before I can merge your 
Pull-Request here are some guidelines that you need to follow. These guidelines exist not to annoy you, but to keep the 
code base clean, unified and future proof.

## Branch

You should only open pull requests against the `master` branch.

## Unit Tests

Please try to add a test for your pull-request. You can run the unit-tests by calling:

```bash
vendor/bin/phpunit
```

## Performance Tests

Before first run bootstrap script will download all necessary datasets from public repository `php-ai/php-ml-datasets`.

Time performance tests:

```bash
vendor/bin/phpbench run --report=time
```

Memory performance tests:

```bash
vendor/bin/phpbench run --report=memory
```

## Travis

GitHub automatically run your pull request through Travis CI.
If you break the tests, I cannot merge your code, so please make sure that your code is working before opening up a Pull-Request.

## Merge

Please give me time to review your pull requests. I will give my best to review everything as fast as possible, but cannot always live up to my own expectations.

## Coding Standards & Static Analysis

When contributing code to PHP-ML, you must follow its coding standards. To do that, just run:

```bash
composer fix-cs
```
[More about EasyCodingStandard](https://github.com/Symplify/EasyCodingStandard)

Code has to also pass static analysis by [PHPStan](https://github.com/phpstan/phpstan):

```bash
composer phpstan
```


## Documentation

Please update the documentation pages if necessary. You can find them in docs/.

---

Thank you very much again for your contribution!
