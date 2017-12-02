<?php

namespace Drupal\budget\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\budget\Form\SummaryForm;
use Drupal\budget\Form\IncomeForm;
use Drupal\budget\Form\IncomeCategoryForm;
use Drupal\budget\Form\TransactionForm;

/**
 * Display a chart demonstrating pie charts.
 */
class Summary extends ControllerBase 
{
   public function content()
   {
      $form_class = '\Drupal\budget\Form\SummaryForm';
      $output['form'] = \Drupal::formBuilder()->getForm($form_class);

      $data = $this->getData();

      $options = [
         "series" => [
            "stack" => TRUE,
            "bars" => [
               "show" => TRUE,
               "barWidth" => .6,
               "align" => "center",
            ],
         ],
         "xaxis" => [
            "mode" => "categories",
            "ticklength" => 0,
         ],
         "yaxes" => [ 
            [
               "min" => 0,
            ],
            [
               "min" => 0,
               "alignTicksWithAxis" => TRUE,
               "position" => "right",
            ],
         ],
         "legend" => [
            "position" => "nw",
         ],
      ];

      $output['flot'] = [
         '#type' => 'flot',
         '#theme' => 'budget_chart_series_toggle',
         '#options' => $options,
         '#data' => $data,
      ];

      return $output;
   }

   private function getData()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');

      $endTime = SummaryForm::getEndTime();
      $startTime = SummaryForm::getStartTime();
      $selectedBudgetCategories = $tempstore->get('transaction_categories');
      $selectedIncomeCategories = $tempstore->get('income_categories');

      // Build the series by getting the monthly data from the database
      $firstMonth = true;
      $currentTime = $startTime;
      $data = [];
      $total = [];
      $overallTotal;
      while($currentTime <= $endTime)
      {
         $begin = $currentTime;
         $dateTime = new \DateTime();
         $dateTime->setTimestamp($currentTime);
         $dateTime->modify('last day of this month');
         $end = $dateTime->getTimestamp();

         if($end > $endTime)
         {
            $end = $endTime;
         }

         // Get the income data for this month
         $dateTime->setTimestamp($begin);
         foreach($selectedIncomeCategories as $incomeCategory)
         {
            $name = null;

            $amountEarnedQuery = db_select('income', 'i')
               ->fields('i', array('timestamp', 'amount', 'category'))
               ->condition('timestamp', array($begin, $end), 'BETWEEN')
               ->condition('category', $incomeCategory);
            $amountEarnedQuery->addExpression('SUM(amount)', 'sum_of_amount');
            $result = $amountEarnedQuery->execute()->fetchAll();
            $amountEarned = $result[0]->sum_of_amount;

            $result = IncomeCategoryForm::getIncomeCategoryContents($incomeCategory);
            foreach($result as $category)
            {
               $name = $category->category;
            }

            if(!$name)
            {
               $name = "-";
            }

            if(!$amountEarned)
            {
               $amountEarned = 0;
            }

            $data[$incomeCategory]['data'][] = [t($dateTime->format('m/y')), $amountEarned];
            $data[$incomeCategory]['label'] = $name;
            $data[$incomeCategory]['yaxis'] = 1;

            $index = 10000 + $incomeCategory;
            $total[$incomeCategory] = $total[$incomeCategory] + $amountEarned;

            $data[$index]['data'][] = [t($dateTime->format('m/y')), $total[$incomeCategory]];
            $data[$index]['label'] = t("Total " . $name);
            $data[$index]['yaxis'] = 2;
            $data[$index]['lines'] = array('show' => TRUE, 'fill' => FALSE);
            $data[$index]['bars'] = array('show' => FALSE);
            $data[$index]['stack'] = null;

            $overallTotal = $amountEarned + $overallTotal;
         }
         
         $data[20000]['data'][] = [t($dateTime->format('m/y')), $overallTotal];
         $data[20000]['label'] = t("Overall Total ");
         $data[20000]['yaxis'] = 2;
         $data[20000]['lines'] = array('show' => TRUE, 'fill' => FALSE);
         $data[20000]['bars'] = array('show' => FALSE);
         $data[20000]['stack'] = null;
         
         // Set the current time to the first day of the next month
         $dateTime->setTimestamp($currentTime);
         $nextTime = $dateTime->modify('+1 month');
         $nextTime->modify ('first day of this month');
         $currentTime = $nextTime->getTimestamp();
      }

      return $data;
   }
}

