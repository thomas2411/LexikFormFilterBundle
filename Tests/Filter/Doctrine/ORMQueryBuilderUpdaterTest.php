<?php

namespace Lexik\Bundle\FormFilterBundle\Tests\Filter\Doctrine;

use Lexik\Bundle\FormFilterBundle\Filter\FilterOperands;

use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\RegisterKernelListenersPass;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Lexik\Bundle\FormFilterBundle\DependencyInjection\LexikFormFilterExtension;
use Lexik\Bundle\FormFilterBundle\DependencyInjection\Compiler\FilterTransformerCompilerPass;
use Lexik\Bundle\FormFilterBundle\Filter\Extension\Type\NumberFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Extension\Type\TextFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Extension\Type\BooleanFilterType;
use Lexik\Bundle\FormFilterBundle\Filter\Transformer\TransformerAggregator;
use Lexik\Bundle\FormFilterBundle\Filter\QueryBuilderUpdater;
use Lexik\Bundle\FormFilterBundle\Tests\TestCase;
use Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Filter\EmbedFilterType;
use Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Filter\RangeFilterType;
use Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemCallbackFilterType;
use Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemFilterType;

/**
 * Filter query builder tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ORMQueryBuilderUpdaterTest extends DoctrineQueryBuilderUpdater
{
    public function testBuildQuery()
    {
        parent::createBuildQueryTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i WHERE i.name LIKE \'blabla\'',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i WHERE i.name LIKE \'blabla\' AND i.position > 2',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i WHERE i.name LIKE \'blabla\' AND i.position > 2 AND i.enabled = 1',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i WHERE i.name LIKE \'blabla\' AND i.position > 2 AND i.enabled = 1',
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i WHERE i.name LIKE \'%blabla\' AND i.position <= 2 AND i.createdAt = \'2013-09-27\'',
        ));
    }

    public function testApplyFilterOption()
    {
        parent::createApplyFilterOptionTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i WHERE i.name <> \'blabla\' AND i.position <> 2',
        ));
    }

    public function testNumberRange()
    {
        parent::createNumberRangeTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i WHERE i.position > 1 AND i.position < 3',
        ));
    }

    public function testNumberRangeDefaultValues()
    {
        parent::createNumberRangeDefaultValuesTest('getDQL', array(
            'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i WHERE i.default_position >= 1 AND i.default_position <= 3',
        ));
    }

    public function testDateRange()
    {
        parent::createDateRangeTest('getDQL', array(
                'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i WHERE i.createdAt <= \'2012-05-22\' AND i.createdAt >= \'2012-05-12\'',
        ));
    }

    public function testEmbedFormFilter()
    {
        // doctrine query builder without any joins
        $form = $this->formFactory->create(new EmbedFilterType());
        $filterQueryBuilder = $this->initQueryBuilder();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->bind(array('name' => 'dude', 'options' => array('label' => 'color', 'rank' => 3)));

        $expectedDql = 'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i';
        $expectedDql .= ' LEFT JOIN i.options opt WHERE i.name LIKE \'dude\' AND opt.label LIKE \'color\' AND opt.rank = 3';
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());

        // doctrine query builder with joins
        $form = $this->formFactory->create(new EmbedFilterType());
        $filterQueryBuilder = $this->initQueryBuilder();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $doctrineQueryBuilder->leftJoin('i.options', 'o');
        $form->bind(array('name' => 'dude', 'options' => array('label' => 'size', 'rank' => 5)));

        $expectedDql = 'SELECT i FROM Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity i';
        $expectedDql .= ' LEFT JOIN i.options o WHERE i.name LIKE \'dude\' AND o.label LIKE \'size\' AND o.rank = 5';

        $filterQueryBuilder->setParts(array('i.options' => 'o'));
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);

        $this->assertEquals($expectedDql, $doctrineQueryBuilder->getDql());
    }

    protected function createDoctrineQueryBuilder()
    {
        return $this->em
                    ->createQueryBuilder()
                    ->select('i')
                    ->from('Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity', 'i');
    }
}
