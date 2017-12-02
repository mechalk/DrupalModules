<?php

namespace Drupal\budget\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the SummaryForm form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SummaryForm extends FormBase
{
   public static function getStartTime($endTime=NULL)
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');

      $startTime = $tempstore->get('startTime');
      if($startTime == NULL)
      {
         if($endTime == NULL)
         {
            $endTime = time();
         }

         $endYear = date("Y", $endTime);
         $startTime = strtotime(t("1/1/" . $endYear));
         $tempstore->set('startTime', $startTime);
      }

      return $startTime;
   }

   public static function getEndTime()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');
      $endTime = $tempstore->get('endTime');
      if($endTime == NULL)
      {
         $endTime = time();
         $tempstore->set('endTime', $endTime);
      }

      return $endTime;
   }

   /**
    * Build the simple form.
    *
    * A build form method constructs an array that defines how markup and
    * other form elements are included in an HTML form.
    *
    * @param array $form
    *   Default form array structure.
    * @param FormStateInterface $form_state
    *   Object containing current form state.
    *
    * @return array
    *   The render array defining the elements of the form.
    */
   public function buildForm(array $form, FormStateInterface $form_state)
   {
      $form['summary'] = array();

      $form['summary'][] = $this->getSummaryFilterForm();
      $form['summary'][] = $this->getIncomeSummaryForm();
      $form['summary'][] = $this->getBudgetSummaryForm();

      return $form;
   }

   /**
    * Getter method for Form ID.
    *
    * @return string
    *   The unique ID of the form defined by this class.
    */
   public function getFormId() 
   {
      return 'summary_form';
   }

   /**
    * Implements form validation
    *
    * @param array $form
    *    The render array of the currently built form
    * @param FormStateInterface $form_state
    *    Object describing the current state of the form
    */
   public function validateForm(array &$form, FormStateInterface $form_state)
   {
   }

   /**
    * Implements a form submit handler.
    *
    * The submitForm method is the default method called for any submit elements.
    *
    * @param array $form
    *   The render array of the currently built form.
    * @param FormStateInterface $form_state
    *   Object describing the current state of the form.
    */
   public function submitForm(array &$form, FormStateInterface $form_state) 
   {
      foreach($form_state->getUserInput() as $key=>$value)
      {
         if(strcasecmp($key, "op") == 0)
         {
            $operation = $value;
         }
         else if(strcasecmp($key, "startDate") == 0)
         {
            $startDate = $value;
         }
         else if(strcasecmp($key, "endDate") == 0)
         {
            $endDate = $value;
         }
         else if(strcasecmp($key, "filterCategorySelect") == 0)
         {
            $filterCategory = $value;
         }
         else if(strcasecmp($key, "filterIncomeCategorySelect") == 0)
         {
            $filterIncomeCategory = $value;
         }
      }

      if(strcasecmp($operation, "Filter") == 0)
      {
         $this->filterSummarySubmit($startDate, $endDate, $filterCategory, $filterIncomeCategory);
      }
      else if(strcasecmp($operation, "Reset") == 0)
      {
         $this->filterResetSubmit();
      }
   }

   private function filterSummarySubmit(string &$startDate, string &$endDate, array &$categories = null, array &$incomeCategories = null)
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');

      // Get the end time from the session variable
      $endTime = strtotime($endDate);
      $tempstore->set('endTime', $endTime);

      // Get the start time from the session variable
      $startTime = strtotime($startDate);
      $tempstore->set('startTime', $startTime);

      $tempstore->set('transaction_categories', $categories);
      $tempstore->set('income_categories', $incomeCategories);
   }

   private function filterResetSubmit()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');

      // Reset the end time
      $endTime = time();
      $tempstore->set('endTime', $endTime);
      $endYear = date("Y", $endTime);

      $startTime = strtotime(t("1/1/" . $endYear));
      $tempstore->set('startTime', $startTime);

      $tempstore->set('transaction_categories', NULL);
      $tempstore->set('income_categories', NULL);
   }

   /**
    * Returns the summary filter form
    *
    * @return array
    *    The transaction filter form
    */
   public function getSummaryFilterForm()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');

      // Get the end time from the session variable
      $endTime = $this->getEndTime();

      // Get the start time from the session variable
      $startTime = $this->getStartTime($endTime);

      // Get the budget category select
      $categorySelect = TransactionForm::getBudgetCategorySelect();
      $categorySelect['#multiple'] = TRUE;
      $defaultCategories = $tempstore->get('transaction_categories');
      if($defaultCategories == NULL)
      {
         $defaultCategories = array_keys($categorySelect['#options']);
         $tempstore->set('transaction_categories', $defaultCategories);
      }
      $categorySelect['#default_value'] = $defaultCategories;

      // Get the income category select
      $incomeCategorySelect = IncomeForm::getIncomeCategorySelect();
      $incomeCategorySelect['#multiple'] = TRUE;
      $defaultIncomeCategories = $tempstore->get('income_categories');
      if($defaultIncomeCategories == NULL)
      {
         $defaultIncomeCategories = array_keys($incomeCategorySelect['#options']);
         $tempstore->set('income_categories', $defaultIncomeCategories);
      }
      $incomeCategorySelect['#default_value'] = $defaultIncomeCategories;

      $form = array();

      // Create the filter form
      $form['filter'] = array(
         '#type' => 'details',
         '#title' => t('Filter'),
         '#open' => FALSE,
      );

      $form['filter']['startDate'] = array(
         '#type' => 'date',
         '#default_value' => t(date('Y-m-d', $startTime)),
         '#title' => t('Start Date'),
      );

      $form['filter']['endDate'] = array(
         '#type' => 'date',
         '#default_value' => t(date('Y-m-d', $endTime)),
         '#title' => t('End Date'),
      );

      $form['filter']['filterIncomeCategorySelect'] = $incomeCategorySelect;
      $form['filter']['filterCategorySelect'] = $categorySelect;

      $form['filter']['submit'] = array(
         '#type' => 'submit',
         '#value' => 'Filter',
      );

      $form['filter']['reset'] = array(
         '#type' => 'submit',
         '#value' => 'Reset',
      );

      return $form;

   }

   public function getIncomeSummaryForm()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');
      setLocale(LC_MONETARY, 'en_US.UTF-8');

      $endTime = $this->getEndTime();
      $startTime = $this->getStartTime();
      $incomeCategories = $tempstore->get('income_categories');

      $id = NULL;

      // Determine the total earned
      $items = array();
      $totalEarned = 0;
      foreach($incomeCategories as $incomeCategory)
      {
         $name = null;

         $amountEarnedQuery = db_select('income','i')
            ->fields('i', array('timestamp','amount','category'))
            ->condition('timestamp', array($startTime, $endTime), 'BETWEEN')
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
            $name = "";
         }

         $items[$incomeCategory] = array(
            'category' => $name,
            'earned' => money_format('%.2n', $amountEarned),
         );

         $totalEarned = $totalEarned + $amountEarned;
      }

      $items[10000] = array(
         'category' => t("<b>" . "TOTAL" . "</b>"),
         'earned' => t("<b>" . money_format('%.2n', $totalEarned) . "</b>"),
      );

      // Retrieve the income contents from the database
      $form = array();

      $form['heading'] = array(
         '#markup' => t("<h1><b>Total Earned: " . money_format('%.2n', $totalEarned) . "</b></h1>"),
      );

      $form['incomeSummary'] = array(
         '#type' => 'details',
         '#title' => t("Income Summary for " . date('n/d/Y', $startTime) . ' to ' . date('n/d/Y', $endTime)),
         '#open' => TRUE,
      );

      $header = array(
         'category' => t("Category"),
         'earned' => t("Amount Earned"),
      );

      $form['incomeSummary']['items'] = array(
         '#type' => 'tableselect',
         '#header' => $header,
         '#options' => $items,
      );

      return $form;
   }

   public function getBudgetSummaryForm()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');
      setLocale(LC_MONETARY, 'en_US.UTF-8');

      $endTime = $this->getEndTime();
      $startTime = $this->getStartTime();
      $categories = $tempstore->get('transaction_categories');

      $id = NULL;

      // Determine the total earned
      $items2 = array();
      $totalUsed = 0;
      foreach($categories as $transactionCategory)
      {
         $name = null;

         $amountUsedQuery = db_select('transactions','t')
            ->fields('t', array('timestamp','amount','category'))
            ->condition('timestamp', array($startTime, $endTime), 'BETWEEN')
            ->condition('category', $transactionCategory);
         $amountUsedQuery->addExpression('SUM(amount)', 'sum_of_amount');
         $result = $amountUsedQuery->execute()->fetchAll();
         $amountUsed = $result[0]->sum_of_amount;

         $result = BudgetCategoryForm::getBudgetCategoryContents($transactionCategory);
         foreach($result as $category)
         {
            $name = $category->category;
         }

         if(!$name)
         {
            $name = "";
         }

         $items2[$transactionCategory] = array(
            'category' => $name,
            'used' => money_format('%.2n', $amountUsed),
         );

         $totalUsed = $totalUsed + $amountUsed;
      }

      $items2[10000] = array(
         'category' => t("<b>" . "TOTAL" . "</b>"),
         'used' => t("<b>" . money_format('%.2n', $totalUsed) . "</b>"),
      );

      $form = array();
      $form['heading'] = array(
         '#markup' => t("<h1><b>Total Spent: " . money_format('%.2n', $totalUsed) . "</b></h1>"),
      );

      $form['budgetSummary'] = array(
         '#type' => 'details',
         '#title' => t("Budget Summary for " . date('n/d/Y', $startTime) . ' to ' . date('n/d/Y', $endTime)),
         '#open' => TRUE,
      );

      $header = array(
         'category' => t("Category"),
         'used' => t("Amount Used"),
      );

      $form['budgetSummary']['items2'] = array(
         '#type' => 'tableselect',
         '#header' => $header,
         '#options' => $items2,
      );

      return $form;
   }

}
