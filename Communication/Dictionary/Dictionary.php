<?php

namespace Wafl\Extensions\Communication\Dictionary;

use DblEj\Communication\Http\Util,
    DblEj\Communication\JsonUtil,
    DblEj\Extension\ExtensionBase;

class Dictionary extends ExtensionBase
implements \DblEj\Communication\Integration\IDictionary
{
    public function Initialize(\DblEj\Application\IApplication $app)
    {

    }

    public function GetDefinitions($word)
    {
        $definitions = [];
        $pairs = $this->Lookup($word);
        foreach ($pairs as $pair)
        {
            $definitions[] = $pair[1];
        }
        return array_unique($definitions);
    }

    public function GetPartsOfSpeech($word)
    {
        $partsOfSpeech = [];
        if ($word)
        {
            $pairs = $this->Lookup($word);
            foreach ($pairs as $pair)
            {
                $partsOfSpeech[] = $pair[0];
            }
        }
        return array_unique($partsOfSpeech);
    }

    public function Lookup($word)
    {
        $wordDefPartPairs = [];
        $cleanWord = trim(str_replace("\\/.,!@#$%^&*()+=\][|}{", " ", $word));

        if ($cleanWord)
        {
            $resultsByBook = $this->_callDict($cleanWord);
            foreach ($resultsByBook as $bookName=>$resultByBook)
            {
                switch ($bookName)
                {
                    case "wn":
                        $results = $this->_parseWordnet($cleanWord, $resultByBook);
                        break;
                    case "jargon":
                        $results = $this->_parseJargon($cleanWord, $resultByBook);
                        break;
                    case "gcide":
                        $results = $this->_parseGcide($cleanWord, $resultByBook);
                        break;
                }

                foreach ($results as $result)
                {
                    $wordDefPartPairs[] = $result;
                }
            }
        }
        return $wordDefPartPairs;
    }

    private function _callDict($word)
    {
        $outputLines = null;
        $result = null;

        exec("dict \"$word\" 2> /dev/null", $outputLines, $result);

        $resultsByBook = ["wn"=>[], "jargon"=>[], "gcide"=>[]];
        $currentBook = null;
        foreach ($outputLines as $outputLine)
        {
            $useLine = trim($outputLine);
            if ($useLine != "")
            {
                if (substr($useLine, 0, 4) == "From")
                {
                    $matches = null;
                    if (preg_match("/From.*\[([a-z0-9]+)\]:/", $useLine, $matches) && count($matches) > 1)
                    {
                        $currentBook = $matches[1];
                    }
                } elseif ($currentBook) {
                    $resultsByBook[$currentBook][] = $outputLine;
                }
            }
        }
        return $resultsByBook;
    }

    private function _parseWordnet($word, $outputLines)
    {
        $results = [];
        $resultIdx = -1;
        foreach ($outputLines as $outputLine)
        {
            $useLine = trim($outputLine);
            $matches = null;
            if (preg_match("/(n|v|adj|adv) 1:(.+)/", $useLine, $matches) && count($matches) > 2)
            {
                $resultIdx++;
                $pos = $matches[1];
                $def = $matches[2];
                $results[$resultIdx] = [$pos, $def];
            }
            elseif ($resultIdx > -1 && (!preg_match("/[0-9]:.+/", $useLine, $matches)))
            {
                $results[$resultIdx][1] .= " " . $useLine;
            }
        }
        return $results;
    }

    private function _parseJargon($word, $outputLines)
    {
        $results = [];
        $resultIdx = -1;
        foreach ($outputLines as $outputLine)
        {
            $useLine = trim($outputLine);

            $matches = null;
            if (preg_match("/\s{0,1}(n|v|adj|adv)\./", $useLine, $matches) && count($matches) > 1)
            {
                $resultIdx++;
                $pos = $matches[1];
                $results[$resultIdx] = [$pos, ""];
            }
            elseif ($resultIdx > -1)
            {
                $results[$resultIdx][1] .= $useLine . " ";
            }
        }

        return $results;
    }

    private function _parseGcide($word, $outputLines)
    {
        $results = [];
        $resultIdx = -1;
        $inDefinitionBlock = false;
        foreach ($outputLines as $outputLine)
        {
            $useLine = trim($outputLine);

            $matches = null;
            if (preg_match("/$word .*, (n|v|a|adv)\./i", $useLine, $matches) && count($matches) > 1)
            {
                $resultIdx++;
                $pos = $matches[1];
                if ($pos == "a")
                {
                    $pos = "adj";
                }
                $results[$resultIdx] = [$pos, ""];
                $inDefinitionBlock = false;
            }
            elseif ($resultIdx > -1 && preg_match("/^[0-9]\. /", $useLine, $matches))
            {
                $results[$resultIdx][1] .= substr($useLine, 3) . " ";
                $inDefinitionBlock = true;
            }
            elseif ($resultIdx > -1 && !preg_match("/\[.*\]/", $useLine))
            {
                $results[$resultIdx][1] .= $useLine . " ";
                $inDefinitionBlock = true;
            }
            elseif ($resultIdx > -1 && $inDefinitionBlock)
            {
                $results[$resultIdx][1] .= $useLine . " ";
            }
        }

        return $results;
    }
}