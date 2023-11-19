<?php

namespace Vasilaki\Phone;

class Phone
{

    /**
     * @var int|null
     */
    protected ?int $code = null;

    /**
     * @var int|null
     */
    protected ?int $number = null;

    /**
     * @var array
     */
    protected array $patternSettings = [
        'pattern' => '+7{code}{number}',
        'code' => '\d{3}',
        'number' => '\d{7}'
    ];

    /**
     * @param string $string
     * @return Phone
     */
    public static function createFromString(string $string): Phone
    {
        $className = get_called_class();
        $phone = new $className();

        $string = str_replace([
            '(',
            ')',
            ' ',
            '+',
            '-',
            '_',
            '[',
            ']'
        ], '', $string);

        if (is_numeric($string)) {
            $pattern = '/[7|8]?(?<code>\d{3})(?<number>\d{7})/';
            $matches = [];
            preg_match($pattern, $string, $matches);
            if (isset($matches['code']) && !empty($matches['code'])) {
                $phone->setCode($matches['code']);
            }
            if (isset($matches['number']) && !empty($matches['number'])) {
                $phone->setNumber($matches['number']);
            }
        }

        return $phone;
    }

    /**
     * @param string $string
     * @return boolean
     */
    public static function isValidFromString(string $string): bool
    {
        $phone = self::createFromString($string);
        return !$phone->isEmpty();
    }

    /**
     * @param integer $code
     * @param string $number
     */
    public function __construct($code = null, $number = null)
    {
        if (null !== $code) {
            $this->setCode($code);
        }
        if (null !== $number) {
            $this->setNumber($number);
        }
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return (empty($this->code) || empty($this->number));
    }

    /**
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param integer $code
     * @return $this
     */
    public function setCode($code)
    {
        if (!is_numeric($code)) {
            throw new \InvalidArgumentException('Code must be numeric');
        }
        $this->code = intval($code);
        return $this;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param $number
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return boolean
     */
    protected function hasPattern()
    {
        return (0 < count($this->patternSettings));
    }

    /**
     * @param string $pattern
     * @param string $codePattern
     * @param string $numberPattern
     */
    public function setPattern($pattern, $codePattern = '\d{3}', $numberPattern = '\d{7}')
    {
        $this->patternSettings = [
            'pattern' => $pattern,
            'codePattern' => $codePattern,
            'numberPattern' => $numberPattern
        ];
        $preparedPattern = str_replace([
            '{code}',
            '{number}'
        ], [
            sprintf('(?<code>%s)', $codePattern),
            sprintf('(?<number>%s)', $numberPattern)
        ], $pattern);
        $preparedPattern = sprintf('/^%s$/', $preparedPattern);
        $this->patternSettings['preparedPattern'] = $preparedPattern;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'code' => $this->getCode(),
            'number' => $this->getNumber(),
            'patternSettings' => $this->patternSettings
        ];
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data)
    {
        if (isset($data['code'])) $this->setCode($data['code']);
        if (isset($data['number'])) $this->setNumber($data['number']);
        if (isset($data['patternSettings'])) $this->patternSettings = $data['patternSettings'];
    }

    /**
     * @param string $string
     * @return boolean
     */
    public function isValid($string)
    {
        if ($this->hasPattern()) {
            $matches = [];
            if (preg_match($this->patternSettings['preparedPattern'], $string, $matches)) {
                if (isset($matches['code']) && !empty($matches['code']) && isset($matches['number']) && !empty($matches['number'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $string
     */
    public function fromString($string)
    {
        if ($this->isValid($string)) {
            $matches = [];
            preg_match($this->patternSettings['preparedPattern'], $string, $matches);
            $this->setCode($matches['code']);
            $this->setNumber($matches['number']);
        }
        return $this;
    }

    /**
     * @param string $format
     * @return string
     */
    public function toString($pattern = null)
    {
        if (empty($pattern) && isset($this->patternSettings['pattern'])) {
            $pattern = $this->patternSettings['pattern'];
            $pattern = str_replace([
                '\+',
                '\s',
                '\(',
                '\)'
            ], [
                '+',
                ' ',
                '(',
                ')'
            ], $pattern);
        }
        if (null !== $pattern) {
            $searchReplace = [
                '{code}' => $this->getCode(),
                '{number}' => $this->getNumber()
            ];

            $matches = [];
            preg_match_all('/\{number(?<positions>\d+)\}/im', $pattern, $matches);
            if (isset($matches['positions'])) {
                $number = $this->getNumber();
                $numberLength = strlen($number);
                foreach ($matches['positions'] as $positions) {
                    $replace = '';
                    foreach (str_split($positions) as $position) {
                        if ($position <= $numberLength) {
                            $replace .= substr($number, $position - 1, 1);
                        }
                    }
                    $searchReplace[sprintf('{number%s}', $positions)] = $replace;
                }
            }

            return str_replace(array_keys($searchReplace), array_values($searchReplace), $pattern);
        }
        return trim($this->getCode() . $this->getNumber());
    }

    /**
     * @param $phone
     * @return boolean
     */
    public function equals($phone)
    {
        return (($this->getCode() == $phone->getCode()) && ($this->getNumber() == $phone->getNumber()));
    }

}