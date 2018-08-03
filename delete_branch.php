#! /usr/bin/php


<?php
/**
 * Class: mo
 * Date: 下午8:31
 * @description
 * @author Mjy
 * @email mojiayu@kuainiugroup.com
 */

class Branch
{
    private $force = false;
    private $path = null;
    private $whiteMap = [];

    private $argc = null;
    private $argv = null;

    public function __construct($argc, $argv)
    {
        $this->argc = $argc;
        $this->argv = $argv;
    }

    public function setParam()
    {
        foreach ($this->argv as $index => $argv) {
            if ($index == 0) {
                continue;
            }
            if (strpos(trim($argv), "-") === 0) {
                if (strpos($argv,"h") !== false) {
                    $this->showHelp();
                    exit;
                }
                if (strpos($argv,"f") !== false) {
                    $this->force = true;
                }
            } else if ($this->path == null) {
                $this->path = $argv;
            } else if ($this->whiteMap == null) {
                $this->whiteMap = $this->getWhiteMap($argv);
            } else {
                echo "参数错误 \n";
                $this->showHelp();
            }
        }
    }

    public function showHelp()
    {
        $helpText = <<<SQL
    使用说明:
      php ./delete_branch [-fh] [path] [white ...]
      
      -f "强制删除分支, 无确认信息"
      -h "显示帮助信息"
      path 项目路径
      white 白名单分支 \n
SQL;
        echo $helpText;
    }

    /**
     * @param $whiteMapParams
     *
     * @return array
     */
    public function getWhiteMap($whiteMapParams): array
    {
        $white_map = [];
        if (!empty($whiteMapParams)) {
            $white_map = explode(",", $whiteMapParams);
        }
        return $white_map;
    }

    public function cd()
    {
        $change = chdir($this->path);
        if (!$change) {
            echo "Something was wrong" . PHP_EOL;
            exit(1);
        }
    }


    /**
     * 执行
     */
    public function execute()
    {
        $this->setParam();
        $this->cd();
        $this->deleteBranch();
    }

    protected function isForce()
    {
        return $this->force === true;
    }
    /**
     * @param $branch
     * @param $code
     *
     * @throws ErrorException
     */
    protected function deleteBranch(): void
    {
        exec("git branch", $branch, $code);
        $diff = array_diff(array_map(function ($item) {
            return trim($item);
        }, $branch), $this->whiteMap);
        $branch = array_splice($diff, 0, count($diff)); // 重置索引
        $fp = fopen("php://stdin", "r");
        $count = count($branch);
        if ($code === 0) {
            foreach ($branch as $index => $item) {
                $idx = $index + 1;
                echo "Process : {$idx}  / {$count}" . PHP_EOL;
                if ($item != 'master' && strpos($item, '*') === false) {
                    if ($this->isForce()) {
                        $input = "Y";
                    } else {
                        echo "Please confirm delete {$item} branch [Y/N]" . PHP_EOL;
                        $input = strtoupper(trim(fgets($fp, 1024)));
                    }
                    if ($input == 'Y') {
                        echo "start detele branch {$item}" . PHP_EOL;
                        exec("git branch -D " . $item, $result, $code);
                        if ($code !== 0) {
                            throw new ErrorException('branch:' . $item . ' error, code :' . $code);
                        }
                    } else {
                        echo "{$item}Skip..." . PHP_EOL;
                    }
                } else {
                    echo "{$item} Skip..." . PHP_EOL;
                }
            }
        }
    }
}

$exec = new Branch($argc, $argv);
$exec->execute();
