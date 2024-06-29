<?php

declare(strict_types=1);

/**
 * Class to obtain eigenvalues and eigenvectors of a real matrix.
 *
 * If A is symmetric, then A = `V*D*V'` where the eigenvalue matrix D
 * is diagonal and the eigenvector matrix V is orthogonal (i.e.
 * A = V.times(D.times(V.transpose())) and V.times(V.transpose())
 * equals the identity matrix).
 *
 * If A is not symmetric, then the eigenvalue matrix D is block diagonal
 * with the real eigenvalues in 1-by-1 blocks and any complex eigenvalues,
 * lambda + i*mu, in 2-by-2 blocks, [lambda, mu; -mu, lambda].  The
 * columns of V represent the eigenvectors in the sense that A*V = V*D,
 * i.e. A.times(V) equals V.times(D).  The matrix V may be badly
 * conditioned, or even singular, so the validity of the equation
 * A = V*D*inverse(V) depends upon V.cond().
 *
 * @author Paul Meagher
 * @license PHP v3.0
 *
 * @version 1.1
 *
 *  Slightly changed to adapt the original code to PHP-ML library
 *  @date 2017/04/11
 *
 *  @author Mustafa Karabulut
 */

namespace Phpml\Math\LinearAlgebra;

use Phpml\Math\Matrix;

class EigenvalueDecomposition
{
    /**
     * Row and column dimension (square matrix).
     *
     * @var int
     */
    private $n;

    /**
     * Arrays for internal storage of eigenvalues.
     *
     * @var array
     */
    private $d = [];

    /**
     * @var array
     */
    private $e = [];

    /**
     * Array for internal storage of eigenvectors.
     *
     * @var array
     */
    private $V = [];

    /**
     * Array for internal storage of nonsymmetric Hessenberg form.
     *
     * @var array
     */
    private $H = [];

    /**
     * Working storage for nonsymmetric algorithm.
     *
     * @var array
     */
    private $ort = [];

    /**
     * Used for complex scalar division.
     *
     * @var float
     */
    private $cdivr;

    /**
     * @var float
     */
    private $cdivi;

    /**
     * Constructor: Check for symmetry, then construct the eigenvalue decomposition
     */
    public function __construct(array $arg)
    {
        $this->n = count($arg[0]);
        $symmetric = true;

        for ($j = 0; ($j < $this->n) & $symmetric; ++$j) {
            for ($i = 0; ($i < $this->n) & $symmetric; ++$i) {
                $symmetric = $arg[$i][$j] == $arg[$j][$i];
            }
        }

        if ($symmetric) {
            $this->V = $arg;
            // Tridiagonalize.
            $this->tred2();
            // Diagonalize.
            $this->tql2();
        } else {
            $this->H = $arg;
            $this->ort = [];
            // Reduce to Hessenberg form.
            $this->orthes();
            // Reduce Hessenberg to real Schur form.
            $this->hqr2();
        }
    }

    /**
     * Return the eigenvector matrix
     */
    public function getEigenvectors(): array
    {
        $vectors = $this->V;

        // Always return the eigenvectors of length 1.0
        $vectors = new Matrix($vectors);
        $vectors = array_map(function ($vect) {
            $sum = 0;
            $count = count($vect);
            for ($i = 0; $i < $count; ++$i) {
                $sum += $vect[$i] ** 2;
            }

            $sum **= .5;
            for ($i = 0; $i < $count; ++$i) {
                $vect[$i] /= $sum;
            }

            return $vect;
        }, $vectors->transpose()->toArray());

        return $vectors;
    }

    /**
     * Return the real parts of the eigenvalues<br>
     *  d = real(diag(D));
     */
    public function getRealEigenvalues(): array
    {
        return $this->d;
    }

    /**
     * Return the imaginary parts of the eigenvalues <br>
     *  d = imag(diag(D))
     */
    public function getImagEigenvalues(): array
    {
        return $this->e;
    }

    /**
     * Return the block diagonal eigenvalue matrix
     */
    public function getDiagonalEigenvalues(): array
    {
        $D = [];

        for ($i = 0; $i < $this->n; ++$i) {
            $D[$i] = array_fill(0, $this->n, 0.0);
            $D[$i][$i] = $this->d[$i];
            if ($this->e[$i] == 0) {
                continue;
            }

            $o = $this->e[$i] > 0 ? $i + 1 : $i - 1;
            $D[$i][$o] = $this->e[$i];
        }

        return $D;
    }

    /**
     * Symmetric Householder reduction to tridiagonal form.
     */
    private function tred2(): void
    {
        //  This is derived from the Algol procedures tred2 by
        //  Bowdler, Martin, Reinsch, and Wilkinson, Handbook for
        //  Auto. Comp., Vol.ii-Linear Algebra, and the corresponding
        //  Fortran subroutine in EISPACK.
        $this->d = $this->V[$this->n - 1];
        // Householder reduction to tridiagonal form.
        for ($i = $this->n - 1; $i > 0; --$i) {
            $i_ = $i - 1;
            // Scale to avoid under/overflow.
            $h = $scale = 0.0;
            $scale += array_sum(array_map('abs', $this->d));
            if ($scale == 0.0) {
                $this->e[$i] = $this->d[$i_];
                $this->d = array_slice($this->V[$i_], 0, $this->n - 1);
                for ($j = 0; $j < $i; ++$j) {
                    $this->V[$j][$i] = $this->V[$i][$j] = 0.0;
                }
            } else {
                // Generate Householder vector.
                for ($k = 0; $k < $i; ++$k) {
                    $this->d[$k] /= $scale;
                    $h += $this->d[$k] ** 2;
                }

                $f = $this->d[$i_];
                $g = $h ** .5;
                if ($f > 0) {
                    $g = -$g;
                }

                $this->e[$i] = $scale * $g;
                $h -= $f * $g;
                $this->d[$i_] = $f - $g;

                for ($j = 0; $j < $i; ++$j) {
                    $this->e[$j] = 0.0;
                }

                // Apply similarity transformation to remaining columns.
                for ($j = 0; $j < $i; ++$j) {
                    $f = $this->d[$j];
                    $this->V[$j][$i] = $f;
                    $g = $this->e[$j] + $this->V[$j][$j] * $f;

                    for ($k = $j + 1; $k <= $i_; ++$k) {
                        $g += $this->V[$k][$j] * $this->d[$k];
                        $this->e[$k] += $this->V[$k][$j] * $f;
                    }

                    $this->e[$j] = $g;
                }

                $f = 0.0;

                if ($h == 0.0) {
                    $h = 1e-32;
                }

                for ($j = 0; $j < $i; ++$j) {
                    $this->e[$j] /= $h;
                    $f += $this->e[$j] * $this->d[$j];
                }

                $hh = $f / (2 * $h);
                for ($j = 0; $j < $i; ++$j) {
                    $this->e[$j] -= $hh * $this->d[$j];
                }

                for ($j = 0; $j < $i; ++$j) {
                    $f = $this->d[$j];
                    $g = $this->e[$j];
                    for ($k = $j; $k <= $i_; ++$k) {
                        $this->V[$k][$j] -= ($f * $this->e[$k] + $g * $this->d[$k]);
                    }

                    $this->d[$j] = $this->V[$i - 1][$j];
                    $this->V[$i][$j] = 0.0;
                }
            }

            $this->d[$i] = $h;
        }

        // Accumulate transformations.
        for ($i = 0; $i < $this->n - 1; ++$i) {
            $this->V[$this->n - 1][$i] = $this->V[$i][$i];
            $this->V[$i][$i] = 1.0;
            $h = $this->d[$i + 1];
            if ($h != 0.0) {
                for ($k = 0; $k <= $i; ++$k) {
                    $this->d[$k] = $this->V[$k][$i + 1] / $h;
                }

                for ($j = 0; $j <= $i; ++$j) {
                    $g = 0.0;
                    for ($k = 0; $k <= $i; ++$k) {
                        $g += $this->V[$k][$i + 1] * $this->V[$k][$j];
                    }

                    for ($k = 0; $k <= $i; ++$k) {
                        $this->V[$k][$j] -= $g * $this->d[$k];
                    }
                }
            }

            for ($k = 0; $k <= $i; ++$k) {
                $this->V[$k][$i + 1] = 0.0;
            }
        }

        $this->d = $this->V[$this->n - 1];
        $this->V[$this->n - 1] = array_fill(0, $this->n, 0.0);
        $this->V[$this->n - 1][$this->n - 1] = 1.0;
        $this->e[0] = 0.0;
    }

    /**
     * Symmetric tridiagonal QL algorithm.
     *
     * This is derived from the Algol procedures tql2, by
     * Bowdler, Martin, Reinsch, and Wilkinson, Handbook for
     * Auto. Comp., Vol.ii-Linear Algebra, and the corresponding
     * Fortran subroutine in EISPACK.
     */
    private function tql2(): void
    {
        for ($i = 1; $i < $this->n; ++$i) {
            $this->e[$i - 1] = $this->e[$i];
        }

        $this->e[$this->n - 1] = 0.0;
        $f = 0.0;
        $tst1 = 0.0;
        $eps = 2.0 ** -52.0;

        for ($l = 0; $l < $this->n; ++$l) {
            // Find small subdiagonal element
            $tst1 = max($tst1, abs($this->d[$l]) + abs($this->e[$l]));
            $m = $l;
            while ($m < $this->n) {
                if (abs($this->e[$m]) <= $eps * $tst1) {
                    break;
                }

                ++$m;
            }

            // If m == l, $this->d[l] is an eigenvalue,
            // otherwise, iterate.
            if ($m > $l) {
                do {
                    // Compute implicit shift
                    $g = $this->d[$l];
                    $p = ($this->d[$l + 1] - $g) / (2.0 * $this->e[$l]);
                    $r = hypot($p, 1.0);
                    if ($p < 0) {
                        $r *= -1;
                    }

                    $this->d[$l] = $this->e[$l] / ($p + $r);
                    $this->d[$l + 1] = $this->e[$l] * ($p + $r);
                    $dl1 = $this->d[$l + 1];
                    $h = $g - $this->d[$l];
                    for ($i = $l + 2; $i < $this->n; ++$i) {
                        $this->d[$i] -= $h;
                    }

                    $f += $h;
                    // Implicit QL transformation.
                    $p = $this->d[$m];
                    $c = 1.0;
                    $c2 = $c3 = $c;
                    $el1 = $this->e[$l + 1];
                    $s = $s2 = 0.0;
                    for ($i = $m - 1; $i >= $l; --$i) {
                        $c3 = $c2;
                        $c2 = $c;
                        $s2 = $s;
                        $g = $c * $this->e[$i];
                        $h = $c * $p;
                        $r = hypot($p, $this->e[$i]);
                        $this->e[$i + 1] = $s * $r;
                        $s = $this->e[$i] / $r;
                        $c = $p / $r;
                        $p = $c * $this->d[$i] - $s * $g;
                        $this->d[$i + 1] = $h + $s * ($c * $g + $s * $this->d[$i]);
                        // Accumulate transformation.
                        for ($k = 0; $k < $this->n; ++$k) {
                            $h = $this->V[$k][$i + 1];
                            $this->V[$k][$i + 1] = $s * $this->V[$k][$i] + $c * $h;
                            $this->V[$k][$i] = $c * $this->V[$k][$i] - $s * $h;
                        }
                    }

                    $p = -$s * $s2 * $c3 * $el1 * $this->e[$l] / $dl1;
                    $this->e[$l] = $s * $p;
                    $this->d[$l] = $c * $p;
                    // Check for convergence.
                } while (abs($this->e[$l]) > $eps * $tst1);
            }

            $this->d[$l] += $f;
            $this->e[$l] = 0.0;
        }

        // Sort eigenvalues and corresponding vectors.
        for ($i = 0; $i < $this->n - 1; ++$i) {
            $k = $i;
            $p = $this->d[$i];
            for ($j = $i + 1; $j < $this->n; ++$j) {
                if ($this->d[$j] < $p) {
                    $k = $j;
                    $p = $this->d[$j];
                }
            }

            if ($k != $i) {
                $this->d[$k] = $this->d[$i];
                $this->d[$i] = $p;
                for ($j = 0; $j < $this->n; ++$j) {
                    $p = $this->V[$j][$i];
                    $this->V[$j][$i] = $this->V[$j][$k];
                    $this->V[$j][$k] = $p;
                }
            }
        }
    }

    /**
     * Nonsymmetric reduction to Hessenberg form.
     *
     * This is derived from the Algol procedures orthes and ortran,
     * by Martin and Wilkinson, Handbook for Auto. Comp.,
     * Vol.ii-Linear Algebra, and the corresponding
     * Fortran subroutines in EISPACK.
     */
    private function orthes(): void
    {
        $low = 0;
        $high = $this->n - 1;

        for ($m = $low + 1; $m <= $high - 1; ++$m) {
            // Scale column.
            $scale = 0.0;
            for ($i = $m; $i <= $high; ++$i) {
                $scale += abs($this->H[$i][$m - 1]);
            }

            if ($scale != 0.0) {
                // Compute Householder transformation.
                $h = 0.0;
                for ($i = $high; $i >= $m; --$i) {
                    $this->ort[$i] = $this->H[$i][$m - 1] / $scale;
                    $h += $this->ort[$i] * $this->ort[$i];
                }

                $g = $h ** .5;
                if ($this->ort[$m] > 0) {
                    $g *= -1;
                }

                $h -= $this->ort[$m] * $g;
                $this->ort[$m] -= $g;
                // Apply Householder similarity transformation
                // H = (I -u * u' / h) * H * (I -u * u') / h)
                for ($j = $m; $j < $this->n; ++$j) {
                    $f = 0.0;
                    for ($i = $high; $i >= $m; --$i) {
                        $f += $this->ort[$i] * $this->H[$i][$j];
                    }

                    $f /= $h;
                    for ($i = $m; $i <= $high; ++$i) {
                        $this->H[$i][$j] -= $f * $this->ort[$i];
                    }
                }

                for ($i = 0; $i <= $high; ++$i) {
                    $f = 0.0;
                    for ($j = $high; $j >= $m; --$j) {
                        $f += $this->ort[$j] * $this->H[$i][$j];
                    }

                    $f /= $h;
                    for ($j = $m; $j <= $high; ++$j) {
                        $this->H[$i][$j] -= $f * $this->ort[$j];
                    }
                }

                $this->ort[$m] = $scale * $this->ort[$m];
                $this->H[$m][$m - 1] = $scale * $g;
            }
        }

        // Accumulate transformations (Algol's ortran).
        for ($i = 0; $i < $this->n; ++$i) {
            for ($j = 0; $j < $this->n; ++$j) {
                $this->V[$i][$j] = ($i == $j ? 1.0 : 0.0);
            }
        }

        for ($m = $high - 1; $m >= $low + 1; --$m) {
            if ($this->H[$m][$m - 1] != 0.0) {
                for ($i = $m + 1; $i <= $high; ++$i) {
                    $this->ort[$i] = $this->H[$i][$m - 1];
                }

                for ($j = $m; $j <= $high; ++$j) {
                    $g = 0.0;
                    for ($i = $m; $i <= $high; ++$i) {
                        $g += $this->ort[$i] * $this->V[$i][$j];
                    }

                    // Double division avoids possible underflow
                    $g /= $this->ort[$m];
                    $g /= $this->H[$m][$m - 1];
                    for ($i = $m; $i <= $high; ++$i) {
                        $this->V[$i][$j] += $g * $this->ort[$i];
                    }
                }
            }
        }
    }

    /**
     * Performs complex division.
     *
     * @param int|float $xr
     * @param int|float $xi
     * @param int|float $yr
     * @param int|float $yi
     */
    private function cdiv($xr, $xi, $yr, $yi): void
    {
        if (abs($yr) > abs($yi)) {
            $r = $yi / $yr;
            $d = $yr + $r * $yi;
            $this->cdivr = ($xr + $r * $xi) / $d;
            $this->cdivi = ($xi - $r * $xr) / $d;
        } else {
            $r = $yr / $yi;
            $d = $yi + $r * $yr;
            $this->cdivr = ($r * $xr + $xi) / $d;
            $this->cdivi = ($r * $xi - $xr) / $d;
        }
    }

    /**
     * Nonsymmetric reduction from Hessenberg to real Schur form.
     *
     * Code is derived from the Algol procedure hqr2,
     * by Martin and Wilkinson, Handbook for Auto. Comp.,
     * Vol.ii-Linear Algebra, and the corresponding
     * Fortran subroutine in EISPACK.
     */
    private function hqr2(): void
    {
        //  Initialize
        $nn = $this->n;
        $n = $nn - 1;
        $low = 0;
        $high = $nn - 1;
        $eps = 2.0 ** -52.0;
        $exshift = 0.0;
        $p = $q = $r = $s = $z = 0;
        // Store roots isolated by balanc and compute matrix norm
        $norm = 0.0;

        for ($i = 0; $i < $nn; ++$i) {
            if (($i > $high)) {
                $this->d[$i] = $this->H[$i][$i];
                $this->e[$i] = 0.0;
            }

            for ($j = max($i - 1, 0); $j < $nn; ++$j) {
                $norm += abs($this->H[$i][$j]);
            }
        }

        // Outer loop over eigenvalue index
        $iter = 0;
        while ($n >= $low) {
            // Look for single small sub-diagonal element
            $l = $n;
            while ($l > $low) {
                $s = abs($this->H[$l - 1][$l - 1]) + abs($this->H[$l][$l]);
                if ($s == 0.0) {
                    $s = $norm;
                }

                if (abs($this->H[$l][$l - 1]) < $eps * $s) {
                    break;
                }

                --$l;
            }

            // Check for convergence
            // One root found
            if ($l == $n) {
                $this->H[$n][$n] += $exshift;
                $this->d[$n] = $this->H[$n][$n];
                $this->e[$n] = 0.0;
                --$n;
                $iter = 0;
            // Two roots found
            } elseif ($l == $n - 1) {
                $w = $this->H[$n][$n - 1] * $this->H[$n - 1][$n];
                $p = ($this->H[$n - 1][$n - 1] - $this->H[$n][$n]) / 2.0;
                $q = $p * $p + $w;
                $z = abs($q) ** .5;
                $this->H[$n][$n] += $exshift;
                $this->H[$n - 1][$n - 1] += $exshift;
                $x = $this->H[$n][$n];
                // Real pair
                if ($q >= 0) {
                    if ($p >= 0) {
                        $z = $p + $z;
                    } else {
                        $z = $p - $z;
                    }

                    $this->d[$n - 1] = $x + $z;
                    $this->d[$n] = $this->d[$n - 1];
                    if ($z != 0.0) {
                        $this->d[$n] = $x - $w / $z;
                    }

                    $this->e[$n - 1] = 0.0;
                    $this->e[$n] = 0.0;
                    $x = $this->H[$n][$n - 1];
                    $s = abs($x) + abs($z);
                    $p = $x / $s;
                    $q = $z / $s;
                    $r = ($p * $p + $q * $q) ** .5;
                    $p /= $r;
                    $q /= $r;
                    // Row modification
                    for ($j = $n - 1; $j < $nn; ++$j) {
                        $z = $this->H[$n - 1][$j];
                        $this->H[$n - 1][$j] = $q * $z + $p * $this->H[$n][$j];
                        $this->H[$n][$j] = $q * $this->H[$n][$j] - $p * $z;
                    }

                    // Column modification
                    for ($i = 0; $i <= $n; ++$i) {
                        $z = $this->H[$i][$n - 1];
                        $this->H[$i][$n - 1] = $q * $z + $p * $this->H[$i][$n];
                        $this->H[$i][$n] = $q * $this->H[$i][$n] - $p * $z;
                    }

                    // Accumulate transformations
                    for ($i = $low; $i <= $high; ++$i) {
                        $z = $this->V[$i][$n - 1];
                        $this->V[$i][$n - 1] = $q * $z + $p * $this->V[$i][$n];
                        $this->V[$i][$n] = $q * $this->V[$i][$n] - $p * $z;
                    }

                    // Complex pair
                } else {
                    $this->d[$n - 1] = $x + $p;
                    $this->d[$n] = $x + $p;
                    $this->e[$n - 1] = $z;
                    $this->e[$n] = -$z;
                }

                $n -= 2;
                $iter = 0;
            // No convergence yet
            } else {
                // Form shift
                $x = $this->H[$n][$n];
                $y = 0.0;
                $w = 0.0;
                if ($l < $n) {
                    $y = $this->H[$n - 1][$n - 1];
                    $w = $this->H[$n][$n - 1] * $this->H[$n - 1][$n];
                }

                // Wilkinson's original ad hoc shift
                if ($iter == 10) {
                    $exshift += $x;
                    for ($i = $low; $i <= $n; ++$i) {
                        $this->H[$i][$i] -= $x;
                    }

                    $s = abs($this->H[$n][$n - 1]) + abs($this->H[$n - 1][$n - 2]);
                    $x = $y = 0.75 * $s;
                    $w = -0.4375 * $s * $s;
                }

                // MATLAB's new ad hoc shift
                if ($iter == 30) {
                    $s = ($y - $x) / 2.0;
                    $s *= $s + $w;
                    if ($s > 0) {
                        $s **= .5;
                        if ($y < $x) {
                            $s = -$s;
                        }

                        $s = $x - $w / (($y - $x) / 2.0 + $s);
                        for ($i = $low; $i <= $n; ++$i) {
                            $this->H[$i][$i] -= $s;
                        }

                        $exshift += $s;
                        $x = $y = $w = 0.964;
                    }
                }

                // Could check iteration count here.
                ++$iter;
                // Look for two consecutive small sub-diagonal elements
                $m = $n - 2;
                while ($m >= $l) {
                    $z = $this->H[$m][$m];
                    $r = $x - $z;
                    $s = $y - $z;
                    $p = ($r * $s - $w) / $this->H[$m + 1][$m] + $this->H[$m][$m + 1];
                    $q = $this->H[$m + 1][$m + 1] - $z - $r - $s;
                    $r = $this->H[$m + 2][$m + 1];
                    $s = abs($p) + abs($q) + abs($r);
                    $p /= $s;
                    $q /= $s;
                    $r /= $s;
                    if ($m == $l) {
                        break;
                    }

                    if (abs($this->H[$m][$m - 1]) * (abs($q) + abs($r)) <
                        $eps * (abs($p) * (abs($this->H[$m - 1][$m - 1]) + abs($z) + abs($this->H[$m + 1][$m + 1])))) {
                        break;
                    }

                    --$m;
                }

                for ($i = $m + 2; $i <= $n; ++$i) {
                    $this->H[$i][$i - 2] = 0.0;
                    if ($i > $m + 2) {
                        $this->H[$i][$i - 3] = 0.0;
                    }
                }

                // Double QR step involving rows l:n and columns m:n
                for ($k = $m; $k <= $n - 1; ++$k) {
                    $notlast = $k != $n - 1;
                    if ($k != $m) {
                        $p = $this->H[$k][$k - 1];
                        $q = $this->H[$k + 1][$k - 1];
                        $r = ($notlast ? $this->H[$k + 2][$k - 1] : 0.0);
                        $x = abs($p) + abs($q) + abs($r);
                        if ($x != 0.0) {
                            $p /= $x;
                            $q /= $x;
                            $r /= $x;
                        }
                    }

                    if ($x == 0.0) {
                        break;
                    }

                    $s = ($p * $p + $q * $q + $r * $r) ** .5;
                    if ($p < 0) {
                        $s = -$s;
                    }

                    if ($s != 0) {
                        if ($k != $m) {
                            $this->H[$k][$k - 1] = -$s * $x;
                        } elseif ($l != $m) {
                            $this->H[$k][$k - 1] = -$this->H[$k][$k - 1];
                        }

                        $p += $s;
                        $x = $p / $s;
                        $y = $q / $s;
                        $z = $r / $s;
                        $q /= $p;
                        $r /= $p;
                        // Row modification
                        for ($j = $k; $j < $nn; ++$j) {
                            $p = $this->H[$k][$j] + $q * $this->H[$k + 1][$j];
                            if ($notlast) {
                                $p += $r * $this->H[$k + 2][$j];
                                $this->H[$k + 2][$j] -= $p * $z;
                            }

                            $this->H[$k][$j] -= $p * $x;
                            $this->H[$k + 1][$j] -= $p * $y;
                        }

                        // Column modification
                        for ($i = 0; $i <= min($n, $k + 3); ++$i) {
                            $p = $x * $this->H[$i][$k] + $y * $this->H[$i][$k + 1];
                            if ($notlast) {
                                $p += $z * $this->H[$i][$k + 2];
                                $this->H[$i][$k + 2] -= $p * $r;
                            }

                            $this->H[$i][$k] -= $p;
                            $this->H[$i][$k + 1] -= $p * $q;
                        }

                        // Accumulate transformations
                        for ($i = $low; $i <= $high; ++$i) {
                            $p = $x * $this->V[$i][$k] + $y * $this->V[$i][$k + 1];
                            if ($notlast) {
                                $p += $z * $this->V[$i][$k + 2];
                                $this->V[$i][$k + 2] -= $p * $r;
                            }

                            $this->V[$i][$k] -= $p;
                            $this->V[$i][$k + 1] -= $p * $q;
                        }
                    }  // ($s != 0)
                }  // k loop
            }  // check convergence
        }  // while ($n >= $low)

        // Backsubstitute to find vectors of upper triangular form
        if ($norm == 0.0) {
            return;
        }

        for ($ni = $nn - 1; $ni >= 0; --$ni) {
            $p = $this->d[$ni];
            $q = $this->e[$ni];
            // Real vector
            if ($q == 0) {
                $l = $ni;
                $this->H[$ni][$ni] = 1.0;
                for ($i = $ni - 1; $i >= 0; --$i) {
                    $w = $this->H[$i][$i] - $p;
                    $r = 0.0;
                    for ($j = $l; $j <= $ni; ++$j) {
                        $r += $this->H[$i][$j] * $this->H[$j][$ni];
                    }

                    if ($this->e[$i] < 0.0) {
                        $z = $w;
                        $s = $r;
                    } else {
                        $l = $i;
                        if ($this->e[$i] == 0.0) {
                            if ($w != 0.0) {
                                $this->H[$i][$ni] = -$r / $w;
                            } else {
                                $this->H[$i][$ni] = -$r / ($eps * $norm);
                            }

                            // Solve real equations
                        } else {
                            $x = $this->H[$i][$i + 1];
                            $y = $this->H[$i + 1][$i];
                            $q = ($this->d[$i] - $p) * ($this->d[$i] - $p) + $this->e[$i] * $this->e[$i];
                            $t = ($x * $s - $z * $r) / $q;
                            $this->H[$i][$ni] = $t;
                            if (abs($x) > abs($z)) {
                                $this->H[$i + 1][$ni] = (-$r - $w * $t) / $x;
                            } else {
                                $this->H[$i + 1][$ni] = (-$s - $y * $t) / $z;
                            }
                        }

                        // Overflow control
                        $t = abs($this->H[$i][$ni]);
                        if (($eps * $t) * $t > 1) {
                            for ($j = $i; $j <= $ni; ++$j) {
                                $this->H[$j][$ni] /= $t;
                            }
                        }
                    }
                }

                // Complex vector
            } elseif ($q < 0) {
                $l = $ni - 1;
                // Last vector component imaginary so matrix is triangular
                if (abs($this->H[$ni][$ni - 1]) > abs($this->H[$ni - 1][$ni])) {
                    $this->H[$ni - 1][$ni - 1] = $q / $this->H[$ni][$ni - 1];
                    $this->H[$ni - 1][$ni] = -($this->H[$ni][$ni] - $p) / $this->H[$ni][$ni - 1];
                } else {
                    $this->cdiv(0.0, -$this->H[$ni - 1][$ni], $this->H[$ni - 1][$ni - 1] - $p, $q);
                    $this->H[$ni - 1][$ni - 1] = $this->cdivr;
                    $this->H[$ni - 1][$ni] = $this->cdivi;
                }

                $this->H[$ni][$ni - 1] = 0.0;
                $this->H[$ni][$ni] = 1.0;
                for ($i = $ni - 2; $i >= 0; --$i) {
                    // double ra,sa,vr,vi;
                    $ra = 0.0;
                    $sa = 0.0;
                    for ($j = $l; $j <= $ni; ++$j) {
                        $ra += $this->H[$i][$j] * $this->H[$j][$ni - 1];
                        $sa += $this->H[$i][$j] * $this->H[$j][$ni];
                    }

                    $w = $this->H[$i][$i] - $p;
                    if ($this->e[$i] < 0.0) {
                        $z = $w;
                        $r = $ra;
                        $s = $sa;
                    } else {
                        $l = $i;
                        if ($this->e[$i] == 0) {
                            $this->cdiv(-$ra, -$sa, $w, $q);
                            $this->H[$i][$ni - 1] = $this->cdivr;
                            $this->H[$i][$ni] = $this->cdivi;
                        } else {
                            // Solve complex equations
                            $x = $this->H[$i][$i + 1];
                            $y = $this->H[$i + 1][$i];
                            $vr = ($this->d[$i] - $p) * ($this->d[$i] - $p) + $this->e[$i] * $this->e[$i] - $q * $q;
                            $vi = ($this->d[$i] - $p) * 2.0 * $q;
                            if ($vr == 0.0 && $vi == 0.0) {
                                $vr = $eps * $norm * (abs($w) + abs($q) + abs($x) + abs($y) + abs($z));
                            }

                            $this->cdiv($x * $r - $z * $ra + $q * $sa, $x * $s - $z * $sa - $q * $ra, $vr, $vi);
                            $this->H[$i][$ni - 1] = $this->cdivr;
                            $this->H[$i][$ni] = $this->cdivi;
                            if (abs($x) > (abs($z) + abs($q))) {
                                $this->H[$i + 1][$ni - 1] = (-$ra - $w * $this->H[$i][$ni - 1] + $q * $this->H[$i][$ni]) / $x;
                                $this->H[$i + 1][$ni] = (-$sa - $w * $this->H[$i][$ni] - $q * $this->H[$i][$ni - 1]) / $x;
                            } else {
                                $this->cdiv(-$r - $y * $this->H[$i][$ni - 1], -$s - $y * $this->H[$i][$ni], $z, $q);
                                $this->H[$i + 1][$ni - 1] = $this->cdivr;
                                $this->H[$i + 1][$ni] = $this->cdivi;
                            }
                        }

                        // Overflow control
                        $t = max(abs($this->H[$i][$ni - 1]), abs($this->H[$i][$ni]));
                        if (($eps * $t) * $t > 1) {
                            for ($j = $i; $j <= $ni; ++$j) {
                                $this->H[$j][$ni - 1] /= $t;
                                $this->H[$j][$ni] /= $t;
                            }
                        }
                    } // end else
                } // end for
            } // end else for complex case
        } // end for

        // Vectors of isolated roots
        for ($i = 0; $i < $nn; ++$i) {
            if ($i > $high) {
                for ($j = $i; $j < $nn; ++$j) {
                    $this->V[$i][$j] = $this->H[$i][$j];
                }
            }
        }

        // Back transformation to get eigenvectors of original matrix
        for ($j = $nn - 1; $j >= $low; --$j) {
            for ($i = $low; $i <= $high; ++$i) {
                $z = 0.0;
                for ($k = $low; $k <= min($j, $high); ++$k) {
                    $z += $this->V[$i][$k] * $this->H[$k][$j];
                }

                $this->V[$i][$j] = $z;
            }
        }
    }
}
