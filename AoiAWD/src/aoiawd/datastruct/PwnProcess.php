<?php

namespace aoiawd\datastruct;

class PwnProcess
{
    public $time;
    public $bin;
    public $maps;
    public $socket = [];
    public $stdin = ['group' => 0, 'byte' => 0];
    public $stdout = ['group' => 0, 'byte' => 0];
    public $streamlog = [];
}
