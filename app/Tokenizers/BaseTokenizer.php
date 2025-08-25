<?php

namespace App\Tokenizers;

abstract class BaseTokenizer
{
    protected $tokens = [];

    private string $value;

    private const SPACE_REPLACER = '&!@';
    private const SINGLE_QUOTE_REPLACER = '!*@';

    public function __construct(string $value)
    {

        $value = str_replace("DEFAULT ''''", 'DEFAULT "MAHER"', $value);
        $this->value = str_replace("DEFAULT ''", 'DEFAULT "MAHER"', $value);

    }

    public function iniTokens()
    {
        $prune = false;
        $pruneSingleQuotes = false;
//\(?\'(.+?)?\s(.+?)?\'\)?
//first get rid of any single quoted stuff with '' around it
        if (preg_match_all('/\'\'(.+?)\'\'/', $this->value, $matches)) {
            foreach ($matches[0] as $key => $singleQuoted) {
                $toReplace = $singleQuoted;
                $this->value = str_replace($toReplace, self::SINGLE_QUOTE_REPLACER . $matches[1][$key] . self::SINGLE_QUOTE_REPLACER, $this->value);
                $pruneSingleQuotes = true;
            }
        }
        if (preg_match_all("/'(.+?)'/", $this->value, $matches)) {
            foreach ($matches[0] as $quoteWithSpace) {
                //we've got an enum or set that has spaces in the text
                //so we'll convert to a different character so it doesn't get pruned
                $toReplace = $quoteWithSpace;
                $this->value = str_replace($toReplace, str_replace(' ', self::SPACE_REPLACER, $toReplace), $this->value);
                $prune = true;
            }
        }
        $this->tokens = array_map(function ($item) {
            return trim($item, ', ');
        }, str_getcsv($this->value, ' ', "'"));
        if ($prune) {
            $this->tokens = array_map(function ($item) {
                return str_replace(self::SPACE_REPLACER, ' ', $item);
            }, $this->tokens);
        }
        if ($pruneSingleQuotes) {
            $this->tokens = array_map(function ($item) {
                return str_replace(self::SINGLE_QUOTE_REPLACER, '\'', $item);
            }, $this->tokens);
        }
        return $this->tokens;
    }
    public static function make(string $line)
    {
        return new static($line);
    }

    /**
     * @param string $line
     * @return static
     */
    public static function parse(string $line)
    {
        $tokenizer = (new static($line));
        $tokenizer->iniTokens();
        $tokenize = $tokenizer->tokenize();
       /*  if(str_contains($line, 'ref_no'))
        dd([$tokenize->definition, $line]); */

        return $tokenize;
    }
    public static function mParse(string $line, $tableName)
    {
        $tokenizer = (new static($line));
        $tokens = $tokenizer->iniTokens();
        $tokenize = $tokenizer->tokenize();
        if ($tableName == 'nationalities') {
            //static::iniTokens($line);
            if (str_contains($line, 'country_ar')) {
                dd(['tokens' => $tokens, 'tokenize' => $tokenize]);
            }

        }

        return $tokenize;

    }

    protected function parseColumn($value)
    {
        return trim($value, '` ');
    }

    protected function columnsToArray($string)
    {
        $string = trim($string, '()');

        return array_map(fn($item) => $this->parseColumn($item), explode(',', $string));
    }

    protected function consume()
    {
        return array_shift($this->tokens);
    }

    protected function putBack($value)
    {
        array_unshift($this->tokens, $value);
    }
     public function tokenize(): self{
        return $this;
     }
}
