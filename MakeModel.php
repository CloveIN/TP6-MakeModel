<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Config;
use think\facade\Db;
use think\facade\App;

class MakeModel extends Command
{
    protected function configure()
    {
        $this->setName('makeModel')
            ->addOption('type', 'type', Option::VALUE_OPTIONAL, '构建类型', 'model')
            ->addOption('AppName', 'an', Option::VALUE_OPTIONAL, '应用名称', 'user')
            ->setDescription('自动构建数据库模型文件');
    }

    protected function execute(Input $input, Output $output)
    {
        $buildType = $input->getOption('type');
        switch ($buildType) {
            case 'model':
                $this->makeModel($input, $output);
                break;
            case 'facade':
                $this->makeFacade($input, $output);
                break;
            case 'trait':
                $this->makeTrait($input, $output);
                break;
            case 'logic':
                $this->makeLogic($input, $output);
                break;
            default:
                $output->writeln('请输入构建类型');
                break;
        }


    }

    //构建数据库模型文件
    protected function makeModel($input, $output) {
        $appName = $input->getOption('AppName');
        $database = Config::get('database.connections');
        $output_dir = App::getAppPath() . $appName . '\model\\';
        foreach ($database as $k => $v) {
            $sql = sprintf("select table_name, table_comment from information_schema.tables where table_schema='%s'",$k);
            $res[$k] = Db::query($sql);
        }
        foreach ($res as $k => $v) {
            $outPutFolder = explode('_', $k);
            $output_dir1 = $output_dir . $outPutFolder[1];
            if (!is_dir($output_dir1)) {
                mkdir($output_dir1, 0555, true);
            }
            foreach ($v as $k1 => $v1){
                $content = file_get_contents(__DIR__ . '/stub/model.stub');
                $sql = sprintf("SELECT column_name,column_comment,data_type FROM information_schema.columns WHERE table_name='%s' AND table_schema='%s'", $v1['table_name'],$k);
                $tableInfo = Db::query($sql);
                $namespace = 'app\\'. $appName .'\model\\'. $outPutFolder[1];
                $className =  'Gc' . $this->toCamelCase($v1['table_name']) . 'Model';
                $types = null;
                $readonly = null;
                $len = count($tableInfo);
                $tableName = $v1['table_name'];
                $tableComment = $v1['table_comment'];
                foreach ($tableInfo as $k2 =>$v2) {
                    if(strpos($v2['data_type'], 'int') === false) {
                        //没有找到int，就代表是string
                        $type = 'string';
                    } else {
                        $type = 'int';
                    }
                    if (($len - 1) != $k2) {
                        $types .= sprintf("        '%s'=>'%s',", $v2['column_name'], $type) . PHP_EOL;
                    } else {
                        $types .= sprintf("        '%s'=>'%s',", $v2['column_name'], $type);
                    }
                    if ($v2['column_name'] == 'id') {
                        $readonly = "'id'";
                    }
                    if ($v2['column_name'] == 'create_time') {
                        $readonly .= ",'create_time'";
                    }
                }
                $file = $output_dir1 .'\\'. $className . '.php';
                if (is_file($file)) {
                    $output->writeln('文件' . $file . '已存在!');
                    continue;
                }
                $content = str_replace([
                    '{%namespace%}',
                    '{%className%}',
                    '{%tableComment%}',
                    '{%tableName%}',
                    '{%types%}',
                    '{%readonly%}'
                ], [
                    $namespace,
                    $className,
                    $tableComment,
                    $tableName,
                    $types,
                    $readonly
                ], $content);
                file_put_contents($file, $content);
                $output->writeln('write:' . $file);
                $output->writeln('make model success!');
            }
        }
    }

    protected function makeFacade ($input, $output){
        $appName = $input->getOption('AppName');
        $database = Config::get('database.connections');
        $output_dir = App::getAppPath() . $appName . '\facade\\';
        foreach ($database as $k => $v) {
            $sql = sprintf("select table_name, table_comment from information_schema.tables where table_schema='%s'",$k);
            $res[$k] = Db::query($sql);
        }
        foreach ($res as $k => $v) {
            $outPutFolder = explode('_', $k);
            $output_dir1 = $output_dir . $outPutFolder[1];
            if (!is_dir($output_dir1)) {
                mkdir($output_dir1, 0555, true);
            }
            foreach ($v as $k1 => $v1){
                $content = file_get_contents(__DIR__ . '/stub/facade.stub');
                $namespace = 'app\\'. $appName .'\facade\\'. $outPutFolder[1];
                $className =  $this->toCamelCase($v1['table_name']) . 'Facade';
                $file = $output_dir1 .'\\'. $className . '.php';
                $logicFile = 'app\\'. $appName .'\logic\\'. $outPutFolder[1] .'\\'. $this->toCamelCase($v1['table_name']) . 'Logic';
                if (is_file($file)) {
                    $output->writeln('文件' . $file . '已存在!');
                    continue;
                }
                $content = str_replace([
                    '{%namespace%}',
                    '{%className%}',
                    '{%logicFile%}',
                ], [
                    $namespace,
                    $className,
                    $logicFile,
                ], $content);
                file_put_contents($file, $content);
                $output->writeln('write:' . $file);
                $output->writeln('make facade success!');
            }
        }
    }

    protected function makeTrait ($input, $output){
        $appName = $input->getOption('AppName');
        $database = Config::get('database.connections');
        $output_dir = App::getAppPath() . $appName . '\lib\traits\\';
        foreach ($database as $k => $v) {
            $sql = sprintf("select table_name, table_comment from information_schema.tables where table_schema='%s'",$k);
            $res[$k] = Db::query($sql);
        }
        foreach ($res as $k => $v) {
            $outPutFolder = explode('_', $k);
            $output_dir1 = $output_dir . $outPutFolder[1];
            if (!is_dir($output_dir1)) {
                mkdir($output_dir1, 0555, true);
            }
            foreach ($v as $k1 => $v1){
                $content = file_get_contents(__DIR__ . '/stub/trait.stub');
                $namespace = 'app\\'. $appName .'\lib\traits\\'. $outPutFolder[1];
                $className =  $this->toCamelCase($v1['table_name']) . 'Trait';
                $userModel = 'app\\'. $appName .'\model\\'. $outPutFolder[1] . '\\Gc' . $this->toCamelCase($v1['table_name']) . 'Model';
                $file = $output_dir1 .'\\'. $className . '.php';
                $databaseTableHump = $this->toCamelCase($outPutFolder[1] . '_' .$this->toCamelCase($v1['table_name']));
                $databaseTable = $outPutFolder[1] . '_' .$this->toCamelCase($v1['table_name']);
                $databaseTableHumpFirstLower = lcfirst($databaseTableHump);
                $modelName = 'Gc' . $this->toCamelCase($v1['table_name']) . 'Model';
                if (is_file($file)) {
                    $output->writeln('文件' . $file . '已存在!');
                    continue;
                }
                $content = str_replace([
                    '{%namespace%}',
                    '{%userModel%}',
                    '{%className%}',
                    '{%databaseTableHump%}',
                    '{%tableName%}',
                    '{%databaseTable%}',
                    '{%databaseTableHumpFirstLower%}',
                    '{%modelName%}',
                ], [
                    $namespace,
                    $userModel,
                    $className,
                    $databaseTableHump,
                    $v1['table_name'],
                    $databaseTable,
                    $databaseTableHumpFirstLower,
                    $modelName,
                ], $content);
                file_put_contents($file, $content);
                $output->writeln('write:' . $file);
                $output->writeln('make trait success!');
            }
        }
    }

    protected function makeLogic ($input, $output){
        $appName = $input->getOption('AppName');
        $database = Config::get('database.connections');
        $output_dir = App::getAppPath() . $appName . '\logic\\';
        foreach ($database as $k => $v) {
            $sql = sprintf("select table_name, table_comment from information_schema.tables where table_schema='%s'",$k);
            $res[$k] = Db::query($sql);
        }
        foreach ($res as $k => $v) {
            $outPutFolder = explode('_', $k);
            $output_dir1 = $output_dir . $outPutFolder[1];
            if (!is_dir($output_dir1)) {
                mkdir($output_dir1, 0555, true);
            }
            foreach ($v as $k1 => $v1){
                $content = file_get_contents(__DIR__ . '/stub/logic.stub');
                $namespace = 'app\\'. $appName .'\logic\\'. $outPutFolder[1];
                $className =  $this->toCamelCase($v1['table_name']) . 'Logic';
                $traitFilePath = 'app\\'. $appName .'\lib\traits\\'. $outPutFolder[1] . '\\' . $this->toCamelCase($v1['table_name']) . 'Trait';
                $file = $output_dir1 .'\\'. $className . '.php';
                $traitFile = $this->toCamelCase($v1['table_name']) . 'Trait';
                $databaseTableHump = $this->toCamelCase($outPutFolder[1] . '_' .$this->toCamelCase($v1['table_name']));
                if (is_file($file)) {
                    $output->writeln('文件' . $file . '已存在!');
                    continue;
                }
                $content = str_replace([
                    '{%namespace%}',
                    '{%traitFilePath%}',
                    '{%className%}',
                    '{%traitFile%}',
                    '{%databaseTableHump%}',
                ], [
                    $namespace,
                    $traitFilePath,
                    $className,
                    $traitFile,
                    $databaseTableHump,
                ], $content);
                file_put_contents($file, $content);
                $output->writeln('write:' . $file);
                $output->writeln('make trait success!');
            }
        }
    }

    function toCamelCase($str)
    {
        $array = explode('_', $str);
        $result = '';

        $len=count($array);
        for($i=0;$i<$len;$i++)
        {
            $result.= ucfirst($array[$i]);
        }
        return $result;
    }
}
