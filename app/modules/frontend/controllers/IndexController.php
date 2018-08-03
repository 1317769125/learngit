<?php

namespace Route\Modules\Frontend\Controllers;

use Phalcon\Mvc\View;

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        $this->view->setRenderLevel(
            View::LEVEL_NO_RENDER
        );
        chdir('../');
        system(" php run getdata begin 20180803");
        exit();
    }

    public function getLogAction()
    {
        chdir('../log/');
        $dir = getcwd();
        echo $dir;
        $dirList = scandir($dir);
        $a = array_slice($dirList, 2);

        print_r($a);
        exit();
        $this->view->disable();
        $this->view->dir = $a;
    }

    public function downloadAction()
    {
        $this->view->setRenderLevel(
            View::LEVEL_NO_RENDER
        );
        $file_name = 'log/'.$this->request->get('file_name');
        chdir('../');
        header("Content-type: application/octet-stream");
        $file =$file_name;
        $filename = basename($file);
        header("Content-Disposition:attachment;filename = ".$filename);
        header("Accept-ranges:bytes");
        header("Accept-length:".filesize($file));
        readfile($file);
    }

}

