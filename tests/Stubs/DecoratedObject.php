<?php

/**
 * This file is part of Cacheable.
 *
 * (c) Eric Chow <yateric@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yateric\Tests\Stubs;

use Yateric\Cacheable\Cacheable;

class DecoratedObject
{
    use Cacheable;

    public function nonStaticMethod($parameter = '')
    {
        return "nonstatic{$parameter}";
    }

    public static function staticMethod($parameter = '')
    {
        return "static{$parameter}";
    }
}