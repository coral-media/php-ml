# Support Vector Classification

Classifier implementing Support Vector Machine based on libsvm.

### Constructor Parameters

* $kernel (int) - kernel type to be used in the algorithm (default Kernel::RBF)
* $cost (float) - parameter C of C-SVC (default 1.0)
* $degree (int) - degree of the Kernel::POLYNOMIAL function (default 3)
* $gamma (float) - kernel coefficient for ‘Kernel::RBF’, ‘Kernel::POLYNOMIAL’ and ‘Kernel::SIGMOID’. If gamma is ‘null’ then 1/features will be used instead.
* $coef0 (float) - independent term in kernel function. It is only significant in ‘Kernel::POLYNOMIAL’ and ‘Kernel::SIGMOID’ (default 0.0)
* $tolerance (float) - tolerance of termination criterion (default 0.001)
* $cacheSize (int) - cache memory size in MB (default 100)
* $shrinking (bool) - whether to use the shrinking heuristics (default true)
* $probabilityEstimates (bool) - whether to enable probability estimates (default false)

```
$classifier = new SVC(Kernel::LINEAR, $cost = 1000);
$classifier = new SVC(Kernel::RBF, $cost = 1000, $degree = 3, $gamma = 6);
```

### Train

To train a classifier, simply provide train samples and labels (as `array`). Example:

```
use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;

$samples = [[1, 3], [1, 4], [2, 4], [3, 1], [4, 1], [4, 2]];
$labels = ['a', 'a', 'a', 'b', 'b', 'b'];

$classifier = new SVC(Kernel::LINEAR, $cost = 1000);
$classifier->train($samples, $labels);
```

You can train the classifier using multiple data sets, predictions will be based on all the training data.

### Predict

To predict sample label use the `predict` method. You can provide one sample or array of samples:

```
$classifier->predict([3, 2]);
// return 'b'

$classifier->predict([[3, 2], [1, 5]]);
// return ['b', 'a']
```

### Probability estimation

To predict probabilities you must build a classifier with `$probabilityEstimates` set to true. Example:

```
use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;

$samples = [[1, 3], [1, 4], [2, 4], [3, 1], [4, 1], [4, 2]];
$labels = ['a', 'a', 'a', 'b', 'b', 'b'];

$classifier = new SVC(
    Kernel::LINEAR, // $kernel
    1.0,            // $cost
    3,              // $degree
    null,           // $gamma
    0.0,            // $coef0
    0.001,          // $tolerance
    100,            // $cacheSize
    true,           // $shrinking
    true            // $probabilityEstimates, set to true
);

$classifier->train($samples, $labels);
```

Then use the `predictProbability` method instead of `predict`:

```
$classifier->predictProbability([3, 2]);
// return ['a' => 0.349833, 'b' => 0.650167]

$classifier->predictProbability([[3, 2], [1, 5]]);
// return [
//   ['a' => 0.349833, 'b' => 0.650167],
//   ['a' => 0.922664, 'b' => 0.0773364],
// ]
```
