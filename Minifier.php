<?php

class compress extends Minifier {
    public $environment;
    public $temp_dir;
    public $base_url;
    
    public function __construct() {
        /* Temp folder path with trailing slash */
        $this->temp_dir='./tmp/';

        /* Base URL for setting absolute path in css aggregation 
            if base_url function exist than use it for codeigniter
        */
        $this->base_url=='http://localhost/fino-intranest/';
        if(function_exists('base_url')) {
            $this->base_url=base_url(); 
        }

        if(!defined('ENVIRONMENT')){
            $this->error("environment Constant not defined",E_USER_ERROR);
        }

        $this->check_dir_status($this->temp_dir);
    }
    /*
        @css_list(array) - array of file path string or single file path

        Return html tag <link> with their supplied list if development mode is on
        Return single <link> tag by aggregating all files content
    */
    public function generate_style_link($css_list) {

        /* Check if valid array */
        if(!is_array($css_list)) {
            $this->error("generate_style_link require 1st parameter as a array");
            die;
        }

        $return_str='';
        /* Css aggregation content */
        $combined_css_content='';

        /* Markup for tags */
        $link_tag='<link href="%s"  media="all" rel="stylesheet" />';
        
        if(ENVIRONMENT == 'production') {
            /* Implode css list for creating aggregating css hash */
            $css_list_str=$css_list;
            
            $css_list_str=implode('', $css_list);
            $combine_hash=hash('sha256',$css_list_str);

            /* Combined css path */
            $combine_path=$this->temp_dir.$combine_hash.'.css';

            /* If combined css is available and creating time grater than any css file return single css file */
            if(file_exists($combine_path)) {
                $combine_modified = filemtime($combine_path);
                $css_list_modified=$this->get_latest_modify($css_list);
                
                if($combine_modified >= $css_list_modified) {
                    return str_replace('%s',$this->base_url.$combine_path, $link_tag);
                }
            }

        }

        /* If array than loop each element */
        foreach ($css_list as $file) {
            if(ENVIRONMENT == 'production') {
                /* Remove base URL from path if full url passed */
                $file=str_replace($this->base_url, '', $file);
                /* If file exist, do minify and concate operation */
                if(file_exists($file)) {

                    /* Add file name as a comment */
                    $this_file_content='/*'.$file.'*/'."\n";
                    $this_file_content.=$this->minify_style(file_get_contents($file));

                    /* replace relative url to absolute path with base url */
                    $this_file_content=preg_replace_callback('/url\(\s*[\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\s*\)/i',function($matches) use($file) {
                        $path = $this->base_url.dirname(trim($file,'./')).'/'.$matches[1];
                        $last = '';
                        while ($path != $last) {
                            $last = $path;
                            $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
                        }
                        return 'url(' . $path . ')';
                    }, $this_file_content);

                    /* Append css string to global variable */
                    $combined_css_content.=$this_file_content;
                }
            }
            else {
                $return_str.=str_replace('%s',$file, $link_tag);
            }
        }

        /* Save aggregating css file */
        if(!empty($combined_css_content)) {
            file_put_contents($combine_path,$combined_css_content);
            return str_replace('%s',$this->base_url.$combine_path, $link_tag);
        }

        return $return_str;
    }

    /*
        @css_list(array) - array of file path string or single file path
        
        Return html tag <script> with their supplied list if development mode is on
        Return single <script> tag by aggregating all files content
    */
    public function generate_script_link($js_list) {
         /* Check if valid array */
        if(!is_array($js_list)) {
            $this->error("generate_script_link require 1st parameter as a array");
            die;
        }

        $return_str='';
        /* js aggregation content */
        $combined_js_content='';

        /* Markup for tags */
        $script_tag=' <script type="text/javascript" src="%s"></script>';
        
        if(ENVIRONMENT == 'production') {
            /* Implode css list for creating aggregating css hash */
            $js_list_str=$js_list;
            
            $js_list_str=implode('', $js_list);
            $combine_hash=hash('sha256',$js_list_str);

            /* Combined css path */
            $combine_path=$this->temp_dir.$combine_hash.'.js';

            /* If combined css is available and creating time grater than any css file return single css file */
            if(file_exists($combine_path)) {
                $combine_modified = filemtime($combine_path);
                $js_list_modified=$this->get_latest_modify($js_list);
                
                if($combine_modified >= $js_list_modified) {
                    return str_replace('%s',$this->base_url.$combine_path, $script_tag);
                }
            }
        }


        /* If array than loop each element */
        foreach ($js_list as $file) {
            if(ENVIRONMENT == 'production') {
                /* Remove base URL from path if full url passed */
                $file=str_replace($this->base_url, '', $file);

                /* If file exist, do minify and aggregation operation */
                if(file_exists($file)) {
                    /* Add file name as a comment */
                    $this_file_content="\n".'/*'.$file.'*/'."\n";
                    $this_file_content.=$this->minify(file_get_contents($file));
                    //$this_file_content .= file_get_contents($file);

                    /* Append css string to global variable */
                    $combined_js_content.=$this_file_content;
                }
            }
            else {
                $return_str.=str_replace('%s',$file, $script_tag);
            }
        }

        /* Save aggregating css file */
        if(!empty($combined_js_content)) {
            file_put_contents($combine_path,$combined_js_content);
            return str_replace('%s',$this->base_url.$combine_path, $script_tag);
        }

        return $return_str;
    }

    /* 
        $file_list(mixed) - List of file path array or single file path
        Return - Latest edited or updated file time
    */
    public function get_latest_modify($file_list) {
        $modified=0;
        if(is_array($file_list)) {
            foreach ($file_list as $file) {
                /* Remove base URL from path if full url passed */
                $file=str_replace($this->base_url, '', $file);

                $file_time  = filemtime($file);
                if($file_time > $modified) {
                    $modified = $file_time;
                }
            }
        }
        else {
            $modified  = filemtime($file_list);
        }
        return $modified;
    }


    /* 
        $contents(string) - Css file content by getting it file_get_content
        
        Return - Minify version of css string.
    */
    function minify_style($contents) {
        // Perform some safe CSS optimizations.
        // Regexp to match comment blocks.
        $comment     = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';
        // Regexp to match double quoted strings.
        $double_quot = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';
        // Regexp to match single quoted strings.
        $single_quot = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";
        // Strip all comment blocks, but keep double/single quoted strings.
        $contents = preg_replace(
            "<($double_quot|$single_quot)|$comment>Ss",
            "$1",
            $contents
            );
        // Remove certain whitespace.
        // There are different conditions for removing leading and trailing
        // whitespace.
        // @see http:       //php.net/manual/regexp.reference.subpatterns.php
        $contents = preg_replace('<
            # Strip leading and trailing whitespace.
                        \s*([@{};,])\s*
            # Strip only leading whitespace from:
            # - Closing parenthesis: Retain "@media (bar) and foo".
                        | \s+([\)])
            # Strip only trailing whitespace from:
            # - Opening parenthesis: Retain "@media (bar) and foo".
            # - Colon: Retain :pseudo-selectors.
            | ([\(:])\s+
            >xS',
            // Only one of the three capturing groups will match, so its reference
            // will contain the wanted value and the references for the
            // two non-matching groups will be replaced with empty strings.
            '$1$2$3',
            $contents
        );
        // End the file with a new line.
        $contents = trim($contents);
        $contents .= "\n";
        return $contents;
    }

    /*
        Get instance of codeingiter
    */
    public function get_ci_instance() {
        ob_start();
        require_once __DIR__ . "./../../index.php";
        ob_end_clean();
        return get_instance();
    }

    function minify_script($input) {

        $input=$this->remove_comments($input);

        return preg_replace([
                // Remove white–space(s) around punctuation(s) [^1]
                '#\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#',
                // Remove the last semi–colon and comma [^2]
                '#[;,]([\]\}])#',
                // Replace `true` with `!0` and `false` with `!1` [^3]
                '#\btrue\b#', '#\bfalse\b#', '#\b(return\s?)\s*\b#',
                // Replace `new Array(x)` with `[x]` … [^4]
                '#\b(?:new\s+)?Array\((.*?)\)#', '#\b(?:new\s+)?Object\((.*?)\)#'
            ], [
                // [^1]
                '$1',
                // [^2]
                '$1',
                // [^3]
                '!0', '!1', '$1',
                // [^4]
                '[$1]', '{$1}'
            ], $input);
    }

    /*
        $input(string) - String of js or css file

        Return  - String by removing single line and multi line comment 
    */
    function remove_comments($input) {
        /* Remove comments */
        $pattern = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/';
        return preg_replace($pattern, '', $input);
    }

    /*
        $dir(String) - File path
        Return - Check directory is exist and is writable
    */
    function check_dir_status($dir) {
        if(file_exists($dir) && is_dir($dir)) {
            if(is_writable($dir)) {
                return true;
            }
            $this->error($dir." Directory is not writable ", E_USER_ERROR);
            return false;
        }
        if(mkdir($dir)) {
            return true;
        } 
        else {
            $this->error($dir." Directory is not writable ", E_USER_ERROR);
            return false;
        }
    }

    /*
        $message( String ) - Error message
        $level - Error level
        
        Generate Error message with backtrack file and line number
    */
    function error($message, $level=E_USER_NOTICE) { 
        $debug_backtrace = debug_backtrace();
        $caller = next($debug_backtrace); 
        trigger_error($message.' in <strong>'.$caller['function'].'</strong> called from <strong>'.$caller['file'].'</strong> on line <strong>'.$caller['line'].'</strong>'."\n<br />error handler", $level); 
    } 
}

/* Helper functions */

/*
    $file_path(array) - List of css file paths
*/
function generate_style_link($file_path) {
    $minify_obj=new compress();
    return $minify_obj->generate_style_link($file_path);
}

function generate_script_link($file_path) {
    $minify_obj=new compress();
    return $minify_obj->generate_script_link($file_path);
}


/*
 * This file is part of the JShrink package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * JShrink
 *
 *
 * @package    JShrink
 * @author     Robert Hafner <tedivm@tedivm.com>
 */


/**
 * Minifier
 *
 * Usage - Minifier::minify($js);
 * Usage - Minifier::minify($js, $options);
 * Usage - Minifier::minify($js, array('flaggedComments' => false));
 *
 * @package JShrink
 * @author Robert Hafner <tedivm@tedivm.com>
 * @license http://www.opensource.org/licenses/bsd-license.php  BSD License
 */


class Minifier
{
    /**
     * The input javascript to be minified.
     *
     * @var string
     */
    protected $input;

    /**
     * The location of the character (in the input string) that is next to be
     * processed.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * The first of the characters currently being looked at.
     *
     * @var string
     */
    protected $a = '';

    /**
     * The next character being looked at (after a);
     *
     * @var string
     */
    protected $b = '';

    /**
     * This character is only active when certain look ahead actions take place.
     *
     *  @var string
     */
    protected $c;

    /**
     * Contains the options for the current minification process.
     *
     * @var array
     */
    protected $options;

    /**
     * Contains the default options for minification. This array is merged with
     * the one passed in by the user to create the request specific set of
     * options (stored in the $options attribute).
     *
     * @var array
     */
    protected static $defaultOptions = array('flaggedComments' => true);

    /**
     * Contains lock ids which are used to replace certain code patterns and
     * prevent them from being minified
     *
     * @var array
     */
    protected $locks = array();

    /**
     * Takes a string containing javascript and removes unneeded characters in
     * order to shrink the code without altering it's functionality.
     *
     * @param  string      $js      The raw javascript to be minified
     * @param  array       $options Various runtime options in an associative array
     * @throws \Exception
     * @return bool|string
     */
    public static function minify($js, $options = array())
    {
        try {
            ob_start();

            $jshrink = new Minifier();
            $js = $jshrink->lock($js);
            $jshrink->minifyDirectToOutput($js, $options);

            // Sometimes there's a leading new line, so we trim that out here.
            $js = ltrim(ob_get_clean());
            $js = $jshrink->unlock($js);
            unset($jshrink);

            return $js;

        } catch (\Exception $e) {

            if (isset($jshrink)) {
                // Since the breakdownScript function probably wasn't finished
                // we clean it out before discarding it.
                $jshrink->clean();
                unset($jshrink);
            }

            // without this call things get weird, with partially outputted js.
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Processes a javascript string and outputs only the required characters,
     * stripping out all unneeded characters.
     *
     * @param string $js      The raw javascript to be minified
     * @param array  $options Various runtime options in an associative array
     */
    protected function minifyDirectToOutput($js, $options)
    {
        $this->initialize($js, $options);
        $this->loop();
        $this->clean();
    }

    /**
     *  Initializes internal variables, normalizes new lines,
     *
     * @param string $js      The raw javascript to be minified
     * @param array  $options Various runtime options in an associative array
     */
    protected function initialize($js, $options)
    {
        $this->options = array_merge(static::$defaultOptions, $options);
        $js = str_replace("\r\n", "\n", $js);
        $js = str_replace('/**/', '', $js);
        $this->input = str_replace("\r", "\n", $js);

        // We add a newline to the end of the script to make it easier to deal
        // with comments at the bottom of the script- this prevents the unclosed
        // comment error that can otherwise occur.
        $this->input .= PHP_EOL;

        // Populate "a" with a new line, "b" with the first character, before
        // entering the loop
        $this->a = "\n";
        $this->b = $this->getReal();
    }

    /**
     * The primary action occurs here. This function loops through the input string,
     * outputting anything that's relevant and discarding anything that is not.
     */
    protected function loop()
    {
        while ($this->a !== false && !is_null($this->a) && $this->a !== '') {

            switch ($this->a) {
                // new lines
                case "\n":
                    // if the next line is something that can't stand alone preserve the newline
                    if (strpos('(-+{[@', $this->b) !== false) {
                        echo $this->a;
                        $this->saveString();
                        break;
                    }

                    // if B is a space we skip the rest of the switch block and go down to the
                    // string/regex check below, resetting $this->b with getReal
                    if($this->b === ' ')
                        break;

                // otherwise we treat the newline like a space

                case ' ':
                    if(static::isAlphaNumeric($this->b))
                        echo $this->a;

                    $this->saveString();
                    break;

                default:
                    switch ($this->b) {
                        case "\n":
                            if (strpos('}])+-"\'', $this->a) !== false) {
                                echo $this->a;
                                $this->saveString();
                                break;
                            } else {
                                if (static::isAlphaNumeric($this->a)) {
                                    echo $this->a;
                                    $this->saveString();
                                }
                            }
                            break;

                        case ' ':
                            if(!static::isAlphaNumeric($this->a))
                                break;

                        default:
                            // check for some regex that breaks stuff
                            if ($this->a === '/' && ($this->b === '\'' || $this->b === '"')) {
                                $this->saveRegex();
                                continue;
                            }

                            echo $this->a;
                            $this->saveString();
                            break;
                    }
            }

            // do reg check of doom
            $this->b = $this->getReal();

            if(($this->b == '/' && strpos('(,=:[!&|?', $this->a) !== false))
                $this->saveRegex();
        }
    }

    /**
     * Resets attributes that do not need to be stored between requests so that
     * the next request is ready to go. Another reason for this is to make sure
     * the variables are cleared and are not taking up memory.
     */
    protected function clean()
    {
        unset($this->input);
        $this->index = 0;
        $this->a = $this->b = '';
        unset($this->c);
        unset($this->options);
    }

    /**
     * Returns the next string for processing based off of the current index.
     *
     * @return string
     */
    protected function getChar()
    {
        // Check to see if we had anything in the look ahead buffer and use that.
        if (isset($this->c)) {
            $char = $this->c;
            unset($this->c);

        // Otherwise we start pulling from the input.
        } else {
            $char = substr($this->input, $this->index, 1);

            // If the next character doesn't exist return false.
            if (isset($char) && $char === false) {
                return false;
            }

            // Otherwise increment the pointer and use this char.
            $this->index++;
        }

        // Normalize all whitespace except for the newline character into a
        // standard space.
        if($char !== "\n" && ord($char) < 32)

            return ' ';

        return $char;
    }

    /**
     * This function gets the next "real" character. It is essentially a wrapper
     * around the getChar function that skips comments. This has significant
     * performance benefits as the skipping is done using native functions (ie,
     * c code) rather than in script php.
     *
     *
     * @return string            Next 'real' character to be processed.
     * @throws \RuntimeException
     */
    protected function getReal()
    {
        $startIndex = $this->index;
        $char = $this->getChar();

        // Check to see if we're potentially in a comment
        if ($char !== '/') {
            return $char;
        }

        $this->c = $this->getChar();

        if ($this->c === '/') {
            return $this->processOneLineComments($startIndex);

        } elseif ($this->c === '*') {
            return $this->processMultiLineComments($startIndex);
        }

        return $char;
    }

    /**
     * Removed one line comments, with the exception of some very specific types of
     * conditional comments.
     *
     * @param  int    $startIndex The index point where "getReal" function started
     * @return string
     */
    protected function processOneLineComments($startIndex)
    {
        $thirdCommentString = substr($this->input, $this->index, 1);

        // kill rest of line
        $this->getNext("\n");

        if ($thirdCommentString == '@') {
            $endPoint = $this->index - $startIndex;
            unset($this->c);
            $char = "\n" . substr($this->input, $startIndex, $endPoint);
        } else {
            // first one is contents of $this->c
            $this->getChar();
            $char = $this->getChar();
        }

        return $char;
    }

    /**
     * Skips multiline comments where appropriate, and includes them where needed.
     * Conditional comments and "license" style blocks are preserved.
     *
     * @param  int               $startIndex The index point where "getReal" function started
     * @return bool|string       False if there's no character
     * @throws \RuntimeException Unclosed comments will throw an error
     */
    protected function processMultiLineComments($startIndex)
    {
        $this->getChar(); // current C
        $thirdCommentString = $this->getChar();

        // kill everything up to the next */ if it's there
        if ($this->getNext('*/')) {

            $this->getChar(); // get *
            $this->getChar(); // get /
            $char = $this->getChar(); // get next real character

            // Now we reinsert conditional comments and YUI-style licensing comments
            if (($this->options['flaggedComments'] && $thirdCommentString === '!')
                || ($thirdCommentString === '@') ) {

                // If conditional comments or flagged comments are not the first thing in the script
                // we need to echo a and fill it with a space before moving on.
                if ($startIndex > 0) {
                    echo $this->a;
                    $this->a = " ";

                    // If the comment started on a new line we let it stay on the new line
                    if ($this->input[($startIndex - 1)] === "\n") {
                        echo "\n";
                    }
                }

                $endPoint = ($this->index - 1) - $startIndex;
                echo substr($this->input, $startIndex, $endPoint);

                return $char;
            }

        } else {
            $char = false;
        }

        if($char === false)
            throw new \RuntimeException('Unclosed multiline comment at position: ' . ($this->index - 2));

        // if we're here c is part of the comment and therefore tossed
        if(isset($this->c))
            unset($this->c);

        return $char;
    }

    /**
     * Pushes the index ahead to the next instance of the supplied string. If it
     * is found the first character of the string is returned and the index is set
     * to it's position.
     *
     * @param  string       $string
     * @return string|false Returns the first character of the string or false.
     */
    protected function getNext($string)
    {
        // Find the next occurrence of "string" after the current position.
        $pos = strpos($this->input, $string, $this->index);

        // If it's not there return false.
        if($pos === false)

            return false;

        // Adjust position of index to jump ahead to the asked for string
        $this->index = $pos;

        // Return the first character of that string.
        return substr($this->input, $this->index, 1);
    }

    /**
     * When a javascript string is detected this function crawls for the end of
     * it and saves the whole string.
     *
     * @throws \RuntimeException Unclosed strings will throw an error
     */
    protected function saveString()
    {
        $startpos = $this->index;

        // saveString is always called after a gets cleared, so we push b into
        // that spot.
        $this->a = $this->b;

        // If this isn't a string we don't need to do anything.
        if ($this->a !== "'" && $this->a !== '"') {
            return;
        }

        // String type is the quote used, " or '
        $stringType = $this->a;

        // Echo out that starting quote
        echo $this->a;

        // Loop until the string is done
        while (true) {

            // Grab the very next character and load it into a
            $this->a = $this->getChar();

            switch ($this->a) {

                // If the string opener (single or double quote) is used
                // output it and break out of the while loop-
                // The string is finished!
                case $stringType:
                    break 2;

                // New lines in strings without line delimiters are bad- actual
                // new lines will be represented by the string \n and not the actual
                // character, so those will be treated just fine using the switch
                // block below.
                case "\n":
                    throw new \RuntimeException('Unclosed string at position: ' . $startpos );
                    break;

                // Escaped characters get picked up here. If it's an escaped new line it's not really needed
                case '\\':

                    // a is a slash. We want to keep it, and the next character,
                    // unless it's a new line. New lines as actual strings will be
                    // preserved, but escaped new lines should be reduced.
                    $this->b = $this->getChar();

                    // If b is a new line we discard a and b and restart the loop.
                    if ($this->b === "\n") {
                        break;
                    }

                    // echo out the escaped character and restart the loop.
                    echo $this->a . $this->b;
                    break;


                // Since we're not dealing with any special cases we simply
                // output the character and continue our loop.
                default:
                    echo $this->a;
            }
        }
    }

    /**
     * When a regular expression is detected this function crawls for the end of
     * it and saves the whole regex.
     *
     * @throws \RuntimeException Unclosed regex will throw an error
     */
    protected function saveRegex()
    {
        echo $this->a . $this->b;

        while (($this->a = $this->getChar()) !== false) {
            if($this->a === '/')
                break;

            if ($this->a === '\\') {
                echo $this->a;
                $this->a = $this->getChar();
            }

            if($this->a === "\n")
                throw new \RuntimeException('Unclosed regex pattern at position: ' . $this->index);

            echo $this->a;
        }
        $this->b = $this->getReal();
    }

    /**
     * Checks to see if a character is alphanumeric.
     *
     * @param  string $char Just one character
     * @return bool
     */
    protected static function isAlphaNumeric($char)
    {
        return preg_match('/^[\w\$\pL]$/', $char) === 1 || $char == '/';
    }

    /**
     * Replace patterns in the given string and store the replacement
     *
     * @param  string $js The string to lock
     * @return bool
     */
    protected function lock($js)
    {
        /* lock things like <code>"asd" + ++x;</code> */
        $lock = '"LOCK---' . crc32(time()) . '"';

        $matches = array();
        preg_match('/([+-])(\s+)([+-])/S', $js, $matches);
        if (empty($matches)) {
            return $js;
        }

        $this->locks[$lock] = $matches[2];

        $js = preg_replace('/([+-])\s+([+-])/S', "$1{$lock}$2", $js);
        /* -- */

        return $js;
    }

    /**
     * Replace "locks" with the original characters
     *
     * @param  string $js The string to unlock
     * @return bool
     */
    protected function unlock($js)
    {
        if (empty($this->locks)) {
            return $js;
        }

        foreach ($this->locks as $lock => $replacement) {
            $js = str_replace($lock, $replacement, $js);
        }

        return $js;
    }
}