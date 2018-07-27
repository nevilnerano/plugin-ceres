<?php

namespace Ceres\Helper;

use IO\Services\CategoryService;
use IO\Services\CustomerService;
use IO\Services\SessionStorageService;
use IO\Services\UrlBuilder\UrlQuery;
use IO\Services\WebstoreConfigurationService;
use Plenty\Modules\Category\Models\Category;
use Plenty\Modules\Category\Models\CategoryDetails;

class NavigationTreeBuilder
{
    private $lang;
    private $isDefaultLang;

    /** @var CategoryService */
    private $categoryService;

    public function prepareNavigationTree($type, $maxLevel = 6)
    {
        /** @var CategoryService $categoryService */
        $categoryService = pluginApp(CategoryService::class);

        $this->lang = pluginApp(SessionStorageService::class)->getLang();
        $this->isDefaultLang = pluginApp(WebstoreConfigurationService::class)->getDefaultLanguage() === $this->lang;

        $this->categoryService = pluginApp(CategoryService::class);

        $tree = $categoryService->getNavigationTree(
            $type,
            $this->lang,
            $maxLevel,
            pluginApp(CustomerService::class)->getContactClassId()
        );

        return $this->buildNavigationTree($tree);
    }

    private function buildNavigationTree($categoryList, $parentUrl = '')
    {
        $tree = [];
        /** @var Category $category */
        foreach( $categoryList as $category )
        {
            /** @var CategoryDetails $categoryDetails */
            $categoryDetails = $category->details->first();
            if ( !is_null($categoryDetails) )
            {
                $treeItem = [
                    'id'        => $category->id,
                    'type'      => $category->type,
                    'url'       => $this->buildCategoryURL($parentUrl, $categoryDetails->nameUrl),
                    'name'      => $categoryDetails->name,
                    'children'  => [],
                    'isCurrent' => $this->categoryService->isCurrent($category),
                    'isOpen'    => $this->categoryService->isOpen($category)
                ];

                if ( !is_null($category->children) && count($category->children) )
                {
                    $treeItem['children'] = $this->buildNavigationTree(
                        $category->children,
                        $categoryDetails->nameUrl
                    );
                }
                $tree[] = $treeItem;
            }
        }

        return $tree;
    }

    private function buildCategoryURL($parentUrl, $urlName)
    {
        /** @var UrlQuery $url */
        $url = pluginApp(
            UrlQuery::class,
            ['path' => $parentUrl, 'lang' => $this->lang]
        );
        $url->join($urlName);
        return $url->toRelativeUrl(!$this->isDefaultLang);
    }
}