# PHP-ML - Machine Learning library for PHP

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![Latest Stable Version](https://img.shields.io/packagist/v/php-ai/php-ml.svg)](https://packagist.org/packages/php-ai/php-ml)
[![Build Status](https://travis-ci.org/php-ai/php-ml.svg?branch=master)](https://travis-ci.org/php-ai/php-ml)
[![Documentation Status](https://readthedocs.org/projects/php-ml/badge/?version=master)](http://php-ml.readthedocs.org/)
[![Total Downloads](https://poser.pugx.org/php-ai/php-ml/downloads.svg)](https://packagist.org/packages/php-ai/php-ml)
[![License](https://poser.pugx.org/php-ai/php-ml/license.svg)](https://packagist.org/packages/php-ai/php-ml)
[![Coverage Status](https://coveralls.io/repos/github/php-ai/php-ml/badge.svg?branch=master)](https://coveralls.io/github/php-ai/php-ml?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/php-ai/php-ml/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/php-ai/php-ml/?branch=master)

<p align="center">
	<img src="https://github.com/php-ai/php-ml/raw/master/docs/assets/php-ml-logo.png" />
</p>

Fresh approach to Machine Learning in PHP. Algorithms, Cross Validation, Neural Network, Preprocessing, Feature Extraction and much more in one library.

PHP-ML requires PHP >= 7.2.

Simple example of classification:
```php
require_once __DIR__ . '/vendor/autoload.php';

use Phpml\Classification\KNearestNeighbors;

$samples = [[1, 3], [1, 4], [2, 4], [3, 1], [4, 1], [4, 2]];
$labels = ['a', 'a', 'a', 'b', 'b', 'b'];

$classifier = new KNearestNeighbors();
$classifier->train($samples, $labels);

$classifier->predict([3, 2]);
// return 'b'
```

## Awards

<a href="http://www.yegor256.com/2016/10/23/award-2017.html">
  <img src="http://www.yegor256.com/images/award/2017/winner-itcraftsmanpl.png" width="400"/></a>

## Documentation

To find out how to use PHP-ML follow [Documentation](http://php-ml.readthedocs.org/).

## Installation

This library is still in beta. However, it can be installed with Composer:

```
composer require php-ai/php-ml
```

## Examples

Example scripts are available in a separate repository [php-ai/php-ml-examples](https://github.com/php-ai/php-ml-examples).

## Features

* Association rule Learning
    * [Apriori](machine-learning/association/apriori.md)
* Classification
    * [SVC](machine-learning/classification/svc.md)
    * [k-Nearest Neighbors](machine-learning/classification/k-nearest-neighbors.md)
    * [Naive Bayes](machine-learning/classification/naive-bayes.md)
* Regression
    * [Least Squares](machine-learning/regression/least-squares.md)
    * [SVR](machine-learning/regression/svr.md)
* Clustering
    * [k-Means](machine-learning/clustering/k-means.md)
    * [DBSCAN](machine-learning/clustering/dbscan.md)
* Metric
    * [Accuracy](machine-learning/metric/accuracy.md)
    * [Confusion Matrix](machine-learning/metric/confusion-matrix.md)
    * [Classification Report](machine-learning/metric/classification-report.md)
* Workflow
    * [Pipeline](machine-learning/workflow/pipeline)
* Neural Network
    * [Multilayer Perceptron Classifier](machine-learning/neural-network/multilayer-perceptron-classifier.md)
* Cross Validation
    * [Random Split](machine-learning/cross-validation/random-split.md)
    * [Stratified Random Split](machine-learning/cross-validation/stratified-random-split.md)
* Feature Selection
    * [Variance Threshold](machine-learning/feature-selection/variance-threshold.md)
    * [SelectKBest](machine-learning/feature-selection/selectkbest.md)
* Preprocessing
    * [Normalization](machine-learning/preprocessing/normalization.md)
    * [Imputation missing values](machine-learning/preprocessing/imputation-missing-values.md)
    * LabelEncoder
* Feature Extraction
    * [Token Count Vectorizer](machine-learning/feature-extraction/token-count-vectorizer.md)
    * [Tf-idf Transformer](machine-learning/feature-extraction/tf-idf-transformer.md)
* Datasets
    * [Array](machine-learning/datasets/array-dataset.md)
    * [CSV](machine-learning/datasets/csv-dataset.md)
    * [Files](machine-learning/datasets/files-dataset.md)
    * [SVM](machine-learning/datasets/svm-dataset.md)
    * [MNIST](machine-learning/datasets/mnist-dataset.md)
    * Ready to use:
        * [Iris](machine-learning/datasets/demo/iris.md)
        * [Wine](machine-learning/datasets/demo/wine.md)
        * [Glass](machine-learning/datasets/demo/glass.md)
* Models management
    * [Persistency](machine-learning/model-manager/persistency.md)
* Math
    * [Distance](math/distance.md)
    * [Matrix](math/matrix.md)
    * [Set](math/set.md)
    * [Statistic](math/statistic.md)


## Contribute

- Guide: [CONTRIBUTING.md](https://github.com/php-ai/php-ml/blob/master/CONTRIBUTING.md)
- Issue Tracker: [github.com/php-ai/php-ml/issues](https://github.com/php-ai/php-ml/issues)
- Source Code: [github.com/php-ai/php-ml](https://github.com/php-ai/php-ml)

You can find more about contributing in [CONTRIBUTING.md](../CONTRIBUTING.md).

## License

PHP-ML is released under the MIT Licence. See the bundled LICENSE file for details.

## Author

Arkadiusz Kondas (@ArkadiuszKondas)
