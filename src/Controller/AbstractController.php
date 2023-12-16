<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as AbstractControllerBase;

use App\Tree\Manager as TreeManager;

class AbstractController extends AbstractControllerBase
{
    public function __construct(protected TreeManager $treeManager)
    {
    }
}
