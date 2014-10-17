<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */
class Hook_symbol_BETA_CSS_PROPERTY
{
    /**
     * Run function for symbol hooks. Searches for tasks to perform.
     *
     * @param  array                     Symbol parameters
     * @return string                    Result
     */
    public function run($param)
    {
        $value = '';

        if (isset($param[0])) {
            $is_supported = array( // No prefixing needed for these
                '-webkit-' => array(
                    'border-top-left-radius' => true,
                    'border-top-right-radius' => true,
                    'border-bottom-left-radius' => true,
                    'border-bottom-right-radius' => true,
                    'border-radius' => true,
                    'box-sizing' => true,
                ),
            );

            $value = '';
            $matches = array();
            if (preg_match('#^opacity:\s*(.*)$#s', $param[0], $matches) != 0) { // Opacity, supported by all except IE8, which is done using a special filter
                $value = "opacity: " . $matches[1] . "\n\t-ms-filter: \"progid:DXImageTransform.Microsoft.Alpha(Opacity=" . float_to_raw_string(round(floatval($matches[1]) * 100.0)) . ")\";";
            } else { // Most cases
                $vendors = array('', '-o-', '-webkit-', '-ms-', '-moz-');
                foreach ($vendors as $prefix) {
                    if (($prefix == '') && (strpos($param[0], 'backface-visibility') !== false)) {
                        continue;
                    }

                    if ((strpos($param[0], ':') !== false) && (isset($is_supported[$prefix][substr($param[0], 0, strpos($param[0], ':'))]))) {
                        continue;
                    }

                    if (substr(trim($param[0]), -1) != ';') {
                        $value .= '; ';
                    }
                    if (preg_match('#^background-image:\s*(\w+-gradient)(.*)$#s', $param[0], $matches) != 0) { // CSS gradients aren't a new property as such, they're a prefixed extension to an existing one
                        $new_style = $matches[2];
                        $old_style = str_replace(array('to right', 'to bottom', 'to bottom right', 'to top right'), array('left', 'top', 'top left', 'bottom left'), $new_style); // This is because the spec changed; at time of writing only MS support the new spec, so for others we'll need to put out both methods (as they'll likely break their self-compatibility)
                        if (($prefix != '-ms-') && ($prefix != '')) {
                            $value .= 'background-image: ' . $prefix . $matches[1] . $old_style;
                        }
                        $value .= 'background-image: ' . $prefix . $matches[1] . $new_style;
                    } else {
                        $value .= $prefix . $param[0];
                    }
                    $value .= "\n\t";
                }
                $value = rtrim($value);
            }
        }

        return $value;
    }
}
