<?php

declare(strict_types=1);

namespace Phpml\SupportVectorMachine;

use Phpml\Exception\InvalidArgumentException;
use Phpml\Exception\InvalidOperationException;
use Phpml\Exception\LibsvmCommandException;
use Phpml\Helper\Trainable;

class SupportVectorMachine
{
    use Trainable;

    /**
     * @var int
     */
    private $type;

    /**
     * @var int
     */
    private $kernel;

    /**
     * @var float
     */
    private $cost;

    /**
     * @var float
     */
    private $nu;

    /**
     * @var int
     */
    private $degree;

    /**
     * @var float|null
     */
    private $gamma;

    /**
     * @var float
     */
    private $coef0;

    /**
     * @var float
     */
    private $epsilon;

    /**
     * @var float
     */
    private $tolerance;

    /**
     * @var int
     */
    private $cacheSize;

    /**
     * @var bool
     */
    private $shrinking;

    /**
     * @var bool
     */
    private $probabilityEstimates;

    /**
     * @var string
     */
    private $javaClassPath;

    /**
     * @var string
     */
    private $varPath;

    /**
     * @var string
     */
    private $model;

    /**
     * @var array
     */
    private $targets = [];

    public function __construct(
        int $type,
        int $kernel,
        float $cost = 1.0,
        float $nu = 0.5,
        int $degree = 3,
        ?float $gamma = null,
        float $coef0 = 0.0,
        float $epsilon = 0.1,
        float $tolerance = 0.001,
        int $cacheSize = 100,
        bool $shrinking = true,
        bool $probabilityEstimates = false
    ) {
        $this->type = $type;
        $this->kernel = $kernel;
        $this->cost = $cost;
        $this->nu = $nu;
        $this->degree = $degree;
        $this->gamma = $gamma;
        $this->coef0 = $coef0;
        $this->epsilon = $epsilon;
        $this->tolerance = $tolerance;
        $this->cacheSize = $cacheSize;
        $this->shrinking = $shrinking;
        $this->probabilityEstimates = $probabilityEstimates;

        $rootPath = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..'])).DIRECTORY_SEPARATOR;
        $this->checkJavaRuntime();
        $this->javaClassPath = $rootPath.'bin'.DIRECTORY_SEPARATOR.'libsvm' . DIRECTORY_SEPARATOR . 'libsvm.jar';
        $this->varPath = $rootPath.'var'.DIRECTORY_SEPARATOR;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setClassPath(string $classPath): void
    {
        $this->ensureDirectorySeparator($classPath);
        $this->verifyClassPath($classPath);

        $this->javaClassPath = $classPath;
    }

    public function setVarPath(string $varPath): void
    {
        if (!is_writable($varPath)) {
            throw new InvalidArgumentException(sprintf('The specified path "%s" is not writable', $varPath));
        }

        $this->ensureDirectorySeparator($varPath);
        $this->varPath = $varPath;
    }

    public function train(array $samples, array $targets): void
    {
        $this->samples = array_merge($this->samples, $samples);
        $this->targets = array_merge($this->targets, $targets);

        $trainingSet = DataTransformer::trainingSet($this->samples, $this->targets, in_array($this->type, [Type::EPSILON_SVR, Type::NU_SVR], true));
        file_put_contents($trainingSetFileName = $this->varPath.uniqid('phpml', true), $trainingSet);
        $modelFileName = $trainingSetFileName.'-model';

        $command = $this->buildTrainCommand($trainingSetFileName, $modelFileName);
        $output = [];
        exec(escapeshellcmd($command).' 2>&1', $output, $return);

        unlink($trainingSetFileName);

        if ($return !== 0) {
            throw new LibsvmCommandException(
                sprintf('Failed running libsvm command: "%s" with reason: "%s"', $command, array_pop($output))
            );
        }

        $this->model = (string) file_get_contents($modelFileName);

        unlink($modelFileName);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return array|string
     *
     * @throws LibsvmCommandException
     */
    public function predict(array $samples)
    {
        $predictions = $this->runSvmPredict($samples, false);

        if (in_array($this->type, [Type::C_SVC, Type::NU_SVC], true)) {
            $predictions = DataTransformer::predictions($predictions, $this->targets);
        } else {
            $predictions = explode(PHP_EOL, trim($predictions));
        }

        if (!is_array($samples[0])) {
            return $predictions[0];
        }

        return $predictions;
    }

    /**
     * @return array|string
     *
     * @throws LibsvmCommandException
     */
    public function predictProbability(array $samples)
    {
        if (!$this->probabilityEstimates) {
            throw new InvalidOperationException('Model does not support probabiliy estimates');
        }

        $predictions = $this->runSvmPredict($samples, true);

        if (in_array($this->type, [Type::C_SVC, Type::NU_SVC], true)) {
            $predictions = DataTransformer::probabilities($predictions, $this->targets);
        } else {
            $predictions = explode(PHP_EOL, trim($predictions));
        }

        if (!is_array($samples[0])) {
            return $predictions[0];
        }

        return $predictions;
    }

    private function runSvmPredict(array $samples, bool $probabilityEstimates): string
    {
        $testSet = DataTransformer::testSet($samples);
        file_put_contents($testSetFileName = $this->varPath.uniqid('phpml', true), $testSet);
        file_put_contents($modelFileName = $testSetFileName.'-model', $this->model);
        $outputFileName = $testSetFileName.'-output';

        $command = $this->buildPredictCommand(
            $testSetFileName,
            $modelFileName,
            $outputFileName,
            $probabilityEstimates
        );
        $output = [];
        exec(escapeshellcmd($command).' 2>&1', $output, $return);

        unlink($testSetFileName);
        unlink($modelFileName);
        $predictions = (string) file_get_contents($outputFileName);

        unlink($outputFileName);

        if ($return !== 0) {
            throw new LibsvmCommandException(
                sprintf('Failed running libsvm command: "%s" with reason: "%s"', $command, array_pop($output))
            );
        }

        return $predictions;
    }

    private function buildTrainCommand(string $trainingSetFileName, string $modelFileName): string
    {
        return sprintf(
            'java -classpath %s svm_train -s %s -t %s -c %s -n %F -d %s%s -r %s -p %F -m %F -e %F -h %d -b %d %s %s',
            $this->javaClassPath,
            $this->type,
            $this->kernel,
            $this->cost,
            $this->nu,
            $this->degree,
            $this->gamma !== null ? ' -g '.$this->gamma : '',
            $this->coef0,
            $this->epsilon,
            $this->cacheSize,
            $this->tolerance,
            $this->shrinking,
            $this->probabilityEstimates,
            escapeshellarg($trainingSetFileName),
            escapeshellarg($modelFileName)
        );
    }

    private function buildPredictCommand(
        string $testSetFileName,
        string $modelFileName,
        string $outputFileName,
        bool $probabilityEstimates
    ): string {
        return sprintf(
            'java -classpath %s svm_predict -b %d %s %s %s',
            $this->javaClassPath,
            $probabilityEstimates ? 1 : 0,
            escapeshellarg($testSetFileName),
            escapeshellarg($modelFileName),
            escapeshellarg($outputFileName)
        );
    }

    private function ensureDirectorySeparator(string &$path): void
    {
        if (substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function verifyClassPath(string $path): void
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException(
                sprintf('The specified path "%s" does not exist', $path)
            );
        }

        if (!file_exists($path . 'libsvm.jar')) {
            throw new InvalidArgumentException(
                sprintf('File "%s" not found', $path . 'libsvm.jar')
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkJavaRuntime(): void
    {
        $paths = explode(PATH_SEPARATOR, getenv("PATH"));
        $checked = [];
        foreach ($paths as $path) {
            if (is_executable($path . DIRECTORY_SEPARATOR . 'java')) {
                $checked[] = 1;
            }
        }
        if (array_sum($checked) < 1) {
            throw new InvalidArgumentException(sprintf('Java command is not available in $PATH="%s"', getenv('PATH')));
        }
    }
}
