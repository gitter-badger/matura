<?php namespace Matura;

use Matura\Core\Builder;
use Matura\Core\ErrorHandler;
use Matura\Core\TestContext;

use Matura\Exceptions\Exception;

/**
 * This classes specific role is unclear at the moment. It mostly manages DSL
 * generation.
 */
class Matura
{
    /** @var Builder $builder The active builder object. */
    protected static $error_handler;

    /** @var string[] $method_names The method names we magically support in or
     *  DSL.
     */
    protected static $method_names = array(
        'suite',
        'xsuite',

        'it',
        'xit',

        'before_all',
        'xbefore_all',

        'before',
        'xbefore',

        'after',
        'xafter',

        'after_all',
        'xafter_all',

        'describe',
        'xdescribe',

        'expect',
        'skip'
    );

    /**
     * Workaround for the lack of https://wiki.php.net/rfc/use_function in
     * PHP < 5.6.
     *
     * Generates source code that can be output to a file or eval'd.
     *
     * ATM this is not designed to be used by Matura's users - even though the
     * variable namespace support might appeal or even be a requirement for some
     * users. Feedback is welcome.
     *
     * It is *meant* to be run before pushing a release.
     *
     * @return string The code that was dumped into functions.php.
     */
    public static function generateDSL($target_namespace = 'Matura\Tests', $method_prefix = '')
    {
        $generate_method = function ($name) use ($method_prefix) {

            $prefixed_name = $method_prefix.$name;

            return <<<EOD

      function $prefixed_name()
      {
          return call_user_func_array(
              array('\Matura\Core\Builder','$name'),
              func_get_args()
          );
      }

EOD;
        };

        $method_code = implode(array_map($generate_method, static::$method_names), "");

        $final_code = <<<EOD
<?php
// Auto-generated with Matura::exportDSL()
namespace $target_namespace {
    $method_code
}
EOD;
        return $final_code;
    }

    public static function loadDSL()
    {
        require_once __DIR__ . '/functions.php';
    }

    public static function init()
    {
        $error_handler = new ErrorHandler();
        set_error_handler(array($error_handler, 'handleError'));
        static::loadDSL();
    }

    public static function cleanup()
    {
        restore_error_handler();
    }

    public static function dynamicDSL($target_namespace = '', $method_prefix = '')
    {
        $source_code = static::generateDSL($target_namespace, $method_prefix);
        eval($source_code);
    }

    public static function exportDSL($target_filename = null, $target_namespace = '', $method_prefix = '')
    {
        $source_code = static::generateDSL($target_namespace, $method_prefix);

        $target_filename = $target_filename ?: __DIR__.'/functions.php';

        file_put_contents($target_filename, $source_code);
    }
}
