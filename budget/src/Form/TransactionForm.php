<?php

namespace Drupal\budget\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Date;

/**
 * Implements the TransactionForm form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class TransactionForm extends FormBase
{
   /** Returns the transaction category select array to add in forms
    *
    * @param string $defaultCategory
    *    Selected category to return
    * @return array
    *    Category select array
    */
   private function getBudgetCategorySelect(string &$defaultCategory=NULL)
   {
      $categorySelect = array();

      // Get the budget category contents from the database
      $result = BudgetCategoryForm::getBudgetCategoryContents($category);

      $categories = array();
      foreach($result as $budgetCategory)
      {
         $categories[$budgetCategory->id] = $budgetCategory->category;
      }

      if(count($categories))
      {
         if(empty($defaultCategory))
         {
            $categorySelect = array(
               '#type' => 'select',
               '#title' => t('Category'),
               '#options' => $categories,
            );
         }
         else
         {
            $categorySelect = array(
               '#type' => 'select',
               '#title' => t('Category'),
               '#options' => $categories,
               '#value' => $defaultCategory,
            );
         }
      }

      return $categorySelect;
   }

   /**
    * Returns the contents of the transaction table in the database.
    *
    * If the id is provided, then the contents for that id are returned.
    * Otherwise, the entire transaction database table is returned
    *
    * @param string $startTime
    *    The start time of the transaction to return
    * @param string $endTime
    *    The end time of the transaction to return
    * @return array
    *    Transaction contents from the database
    */
   public static function getTransactionContents(string &$id=NULL, string &$startTime=NULL, string &$endTime=NULL, array &$categories = null, string &$taxDeduct = null)
   {
      if($id != NULL)
      {
         $query = db_select('transactions', 't')
            ->fields('t', array('id', 'uid', 'payee', 'timestamp', 'amount', 'category', 'tax_deduct'))
            ->condition('id', $id);
      }
      else if($startTime && $endTime)
      {
         $query = db_select('transactions', 't')
            ->fields('t', array('id', 'uid', 'payee', 'timestamp', 'amount', 'category', 'tax_deduct'))
            ->condition('timestamp', array($startTime, $endTime), 'BETWEEN')
            ->orderby('timestamp', 'DESC');

         if($categories)
         {
            $query->condition('category', $categories, 'IN');
         }

         if($taxDeduct)
         {
            $query->condition('tax_deduct', $taxDeduct);
         }
      }
      else
      {
         $query = db_select('transactions', 't')
            ->fields('t', array('id', 'uid', 'payee', 'timestamp', 'amount', 'category', 'tax_deduct'))
            ->orderby('timestamp', 'DESC');
      }

      $result = $query->execute();
      return $result;
   }

   /**
    * Returns the number of transaction entries in the database
    *
    * @return int
    *    Number of transaction entries in the database
    */
   public static function getNumTransactions(string &$startTime=NULL, string &$endTime=NULL)
   {
      if($startTime && $endTime)
      {
         $count = db_select('transactions')
            ->fields(NULL, array('field'))
            ->condition('timestamp', array($startTime, $endTime), 'BETWEEN')
            ->countQuery()
            ->execute()
            ->fetchField();
      }
      else
      {
         $count = db_select('transactions')
            ->fields(NULL, array('field'))
            ->countQuery()
            ->execute()
            ->fetchField();
      }

      return intval($count);
   }

   /**
    * Build the transaction form.
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
      $form['transaction'] = array();

      if($form_state->has('page_num') &&
         $form_state->get('page_num') == 2)
      {
         $form['transaction'][] = $this->getTransactionAddUpdateForm($form_state->get('modifyId'));
      }
      else
      {
         $form['transaction'][] = $this->getTransactionAddUpdateForm();
         $form['transaction'][] = $this->getTransactionFilterForm();
         $form['transaction'][] = $this->getTransactionManageForm();
      }

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
      return 'transaction_form';
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
      foreach($form_state->getUserInput() as $key=>$value)
      {
         if(strcasecmp($key, "op") == 0)
         {
            $operation = $value;
         }
         else if(strcasecmp($key, "payee") == 0)
         {
            $payee = $value;
         }
         else if(strcasecmp($key, "date") == 0)
         {
            $timestamp = $value;
         }
         else if(strcasecmp($key, "amount") == 0)
         {
            $amount = $value;
         }
         else if(strcasecmp($key, "categorySelect") == 0)
         {
            $category = $value;
         }
         else if(strcasecmp($key, "taxDeduct") == 0)
         {
            $taxDeductible = $value;
         }
      }

      if(strcasecmp($operation, "Add Transaction") == 0)
      {
         $this->addUpdateTransactionValidate($form_state, $timestamp, $payee, $amount, $category, $taxDeductible);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         $this->addUpdateTransactionValidate($form_state, $timestamp, $payee, $amount, $category, $taxDeductible);
      }
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
         else if(strcasecmp($key, "payee") == 0)
         {
            $payee = $value;
         }
         else if(strcasecmp($key, "date") == 0)
         {
            $timestamp = $value;
         }
         else if(strcasecmp($key, "amount") == 0)
         {
            $amount = $value;
         }
         else if(strcasecmp($key, "categorySelect") == 0)
         {
            $category = $value;
         }
         else if(strcasecmp($key, "filterCategorySelect") == 0)
         {
            $filterCategory = $value;
         }
         else if(strcasecmp($key, "taxDeduct") == 0)
         {
            $taxDeductible = $value;
         }
         else if(strcasecmp($key, "categories") == 0)
         {
            $selectedIds = $value;
         }
         else if(strcasecmp(substr($key,0,14), "delete_button_") == 0)
         {
            $operation = "Delete Individual";
            $deleteId = substr($key,strrpos($key,'_')+1);
         }
         else if(strcasecmp(substr($key,0,14), "modify_button_") == 0)
         {
            $operation = "Modify Individual";
            $modifyId = substr($key,strrpos($key,'_')+1);
         }
      }

      if(strcasecmp($operation, "Add Transaction") == 0)
      {
         $this->addTransactionSubmit($timestamp, $payee, $amount, $category, $taxDeductible);
      }
      else if(strcasecmp($operation, "Delete Selected") == 0)
      {
         $this->removeSelectedTransactions($selectedIds);
      }
      else if(strcasecmp($operation, "Delete Individual") == 0)
      {
         $this->removeTransactionWithId($deleteId);
      }
      else if(strcasecmp($operation, "Modify Individual") == 0)
      {
         $form_state->set('page_num', 2)
            ->set('modifyId', $modifyId)
            ->setRebuild(TRUE);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         $this->updateTransactionSubmit($timestamp, $payee, $amount, $category, $taxDeductible, $form_state->get('modifyId'));
      }
      else if(strcasecmp($operation, "Filter") == 0)
      {
         $this->filterTransactionSubmit($startDate, $endDate, $taxDeductible, $filterCategory);
      }
      else if(strcasecmp($operation, "Reset") == 0)
      {
         $this->filterResetSubmit();
      }
   }

   /**
    * Custom validation function for validating an transaction add or update
    *
    * @param FormStateInterface $form_state
    *    Object describing the current state of the form.
    * @param string $timestamp
    *    Timestamp of the transaction
    * @param string $amount
    *    Amount of the transaction
    * @param string $category
    *    Category for the transaction
    * @param string $notes
    *    Notes for the transaction
    */
   private function addUpdateTransactionValidate(FormStateInterface $form_state, string &$timestamp, string &$payee, string &$amount, string &$category, string &$taxDeductible = null)
   {
      if(!$timestamp)
      {
         $form_state->setErrorByName('date',
            'Please enter a valid date for the transaction');
      }

      if(!$payee)
      {
         $form_state->setErrorByName('payee',
            'Please enter a payee name for the transaction');
      }

      // Validate the amount is formatted properly
      if(!$amount)
      {
         $form_state->setErrorByName('amount', 'Please enter the amount');
      }
      else if(preg_match('/^[+-]?[0-9]{1,3}(?:,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $amount) != 1)
      {
         $form_state->setErrorByName('amount',
            'Please enter a valid amount for the transaction');
      }

      // Validate that the category ID is in the database
      if(!$category)
      {
         $form_state->setErrorByName('categorySelect', 
            'Please select a category');
      }
      else
      {
         $result = BudgetCategoryForm::getBudgetCategoryContents($category)
            ->fetchField();

         if(empty($result))
         {
            $form_state->setErrorByName('categorySelect', 
               'Please select a valid category');
         }
      }

      // Validate that the date selected is not in the future
      $currentYear = intval(date("Y"));
      $currentMonth = intval(date("n"));
      $currentDay = intval(date("j"));

      $currentTimeString = $currentMonth . "/" . $currentDay . "/" . $currentYear;
      $currentTime = strtotime($currentTimeString);
      $transactionTime = strtotime($timestamp);

      if($transactionTime > $currentTime)
      {
         $form_state->setErrorByName('date', 'Date cannot be in the future');
      }
   }

   /**
    * Custom function for adding an transaction entry
    *
    * @param string $timestamp
    *    Timestamp of the transaction
    * @param string $amount
    *    Amount of the transaction
    * @param string $category
    *    Category for the transaction
    * @param string $notes
    *    Notes for the transaction
    */
   private function addTransactionSubmit(string &$timestamp, string &$payee, string &$amount, string &$category, string &$taxDeductible = null)
   {
      $uid = \Drupal::currentUser()->id();
      $amount = str_replace('$', '', $amount);
      $amount = preg_replace("/([^0-9\\.-])/i", "", $amount);

      $currentTime = strtotime($timestamp);

      try
      {
         if($taxDeductible == null)
         {
            $taxDeductible = 0;
         }

         db_insert('transactions')
            ->fields(array(
               'uid' => $uid,
               'payee' => $payee,
               'timestamp' => $currentTime,
               'amount' => $amount,
               'category' => $category,
               'tax_deduct' => $taxDeductible,
            ))
            ->execute();

         setlocale(LC_MONETARY, 'en_US.UTF-8');
         $debug_message = "Successfully added transaction " . money_format('%.2n', $amount) . " on " . $timestamp;
         drupal_set_message($debug_message, 'status');
      }
      catch (\Exception $e)
      {
         $debug_message = 'db_insert failed. Message=' . $e->getMessage();
         $debug_message .= ', Query=' . $e->query_string;
         drupal_set_message($debug_message, 'error');
      }
   }

   private function filterTransactionSubmit(string &$startDate, string &$endDate, string &$taxDeductible = null, array &$categories = null)
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');

      // Get the end time from the session variable
      $endTime = strtotime($endDate);
      $tempstore->set('endTime', $endTime);

      // Get the start time from the session variable
      $startTime = strtotime($startDate);
      $tempstore->set('startTime', $startTime);

      $tempstore->set('transaction_categories', $categories);
      $tempstore->set('taxDeduct', $taxDeductible);
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

      $categorySelect = $this->getBudgetCategorySelect();
      $defaultCategories = array_keys($categorySelect['#options']);
      $tempstore->set('transaction_categories', $defaultCategories);

      $taxDeductible = 0;
      $tempstore->set('taxDeduct', $taxDeductible);
   }

   /**
    * Custom function for updating an transaction entry
    *
    * @param string $timestamp
    *    Timestamp of the transaction
    * @param string $amount
    *    Amount of the transaction
    * @param string $category
    *    Category for the transaction
    * @param string $notes
    *    Notes for the transaction
    * @param string $id
    *    ID of the transaction entry to update
    */
   private function updateTransactionSubmit(string &$timestamp, string &$payee, string &$amount, string &$category, string &$taxDeductible = null, string &$id)
   {
      if($id)
      {
         $amount = str_replace('$', '', $amount);
         $amount = preg_replace("/([^0-9\\.-])/i", "", $amount);

         $currentTimestamp = strtotime($timestamp);

         try
         {
            if($taxDeductible == null)
            {
               $taxDeductible = 0;
            }

            db_update('transactions')
               ->fields(array(
                  'payee' => $payee,
                  'timestamp' => $currentTimestamp,
                  'amount' => $amount,
                  'category' => $category,
                  'tax_deduct' => $taxDeductible,
               ))
               ->condition('id', $id)
               ->execute();

            setlocale(LC_MONETARY, 'en_US.UTF-8');
            $debug_message = "Successfully updated transaction " . money_format('%.2n', $amount) . " on " . $timestamp;
            drupal_set_message($debug_message, 'status');
         }
         catch (\Exception $e)
         {
            $debug_message = 'db_insert failed. Message=' . $e->getMessage();
            $debug_message .= ', Query=' . $e->query_string;
            drupal_set_message($debug_message, 'error');
         }
      }
   }

   /**
    * Custom function for removing all selected transactions from the database
    *
    * @param array selectedIDs
    *    Array of the selected IDs from the transaction table
    */
   private function removeSelectedTransactions(array &$selectedIDs)
   {
      foreach($selectedIDs as $key => $value)
      {
         if($value)
         {
            $this->removeTransactionWithId($key);
         }
      }
   }

   /**
    * Remove an transaction from the database
    *
    * @param string id
    *    ID of the transaction to remove
    */
   private function removeTransactionWithId(string &$id)
   {
      $num_deleted = db_delete('transactions')
         ->condition('id', $id)
         ->execute();
   }

   /**
    * Returns the transaction add form
    *
    * Builds either the transaction add form or the transaction update form. If the
    * input parameter $modifyId is provided, then the transaction update form
    * will be returned. The modifyId is the ID of the transaction in the database
    * that will be updated. The input fields are pre-populated with
    * the values from the database.
    *
    * @param string $modifyId
    *    ID field from the database of the transaction to modify
    * @return array
    *    The transaction add/update form
    */
   private function getTransactionAddUpdateForm(string &$modifyId=NULL)
   {
      $form = array();

      $categorySelect = $this->getBudgetCategorySelect();

      if(!empty($categorySelect))
      {
         $form['addTransaction'] = array(
            '#type' => 'details',
            '#title' => t('Add a new transaction'),
            '#open' => TRUE,
         );

         $form['addTransaction']['payee'] = array(
            '#type' => 'textfield',
            '#title' => t('Payee'),
            '#size' => 40,
            '#maxLength' => 40,
         );

         $form['addTransaction']['amount'] = array(
            '#type' => 'textfield',
            '#title' => t('Amount'),
            '#size' => 40,
            '#maxLength' => 40,
         );

         $form['addTransaction']['date'] = array(
            '#type' => 'date',
            '#title' => t('Date'),
            '#default_value' => t(date('Y-m-d')),
         );

         $form['addTransaction']['categorySelect'] = $categorySelect;

         $form['addTransaction']['taxDeduct'] = array(
            '#type' => 'checkbox',
            '#title' => t('Tax Deductible'),
            '#default_value' => 0,
         );

         if($modifyId)
         {
            $result = $this->getTransactionContents($modifyId);
            foreach($result as $transaction)
            {
               $categorySelect = $this->getBudgetCategorySelect($transaction->category);
               $form['addTransaction']['payee']['#value'] = $transaction->payee;
               $form['addTransaction']['amount']['#value'] = $transaction->amount;
               $form['addTransaction']['date']['#value'] = t(date('Y-m-d', $transaction->timestamp));
               $form['addTransaction']['categorySelect'] = $categorySelect;
               $form['addTransaction']['taxDeduct']['#value'] = $transaction->tax_deduct;
               $form['addTransaction']['#title'] = t("Update transaction");
               $form['addTransaction']['#open'] = TRUE;
            }

            $form['addTransaction']['submit'] = array(
               '#type' => 'submit',
               '#value' => 'Update',
            );

            $form['addTransaction']['cancel'] = array(
               '#type' => 'submit',
               '#value' => 'Cancel',
            );
         }
         else
         {
            $form['addTransaction']['submit'] = array(
               '#type' => 'submit',
               '#value' => 'Add Transaction',
            );
         }
      }
      else
      {
         drupal_set_message("Add at least one budget category in order to add a transaction", 'error');
      }

      return $form;
   }

   /**
    * Returns the transaction manaagment form
    *
    * Returns a tableselect showing the values for all transaction entries  
    *
    * @return array
    *    The transaction management form
    */
   private function getTransactionManageForm()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');

      // Get the end time from the session variable
      $endTime = $tempstore->get('endTime');
      if($endTime == NULL)
      {
         $endTime = time();
         $tempstore->set('endTime', $endTime);
      }
      $endYear = date("Y", $endTime);

      // Get the start time from the session variable
      $startTime = $tempstore->get('startTime');
      if($startTime == NULL)
      {
         $startTime = strtotime(t("1/1/" . $endYear));
         $tempstore->set('startTime', $startTime);
      }

      // Get the filtered categories
      $filteredCategories = $tempstore->get('transaction_categories');
      $taxDeductible = $tempstore->get('taxDeduct');

      $id = NULL;
      $result = $this->getTransactionContents($id, $startTime, $endTime, $filteredCategories, $taxDeductible);

      $categories = array();

      $form = array();

      // Iterate over the transaction entries and format as strings
      setlocale(LC_MONETARY, 'en_US.UTF-8');
      foreach ($result as $transaction)
      {
         $deleteButton = array(
            '#type' => 'submit',
            '#value' => t('Delete'),
            '#name' => t('delete_button_' . $transaction->id),
         );

         $modifyButton = array(
            '#type' => 'submit',
            '#value' => t('Modify'),
            '#name' => t('modify_button_' . $transaction->id),
         );

         $categorySelect = $this->getBudgetCategorySelect($transaction->category);
         $category = $categorySelect['#options'][$transaction->category];
         if($category == null)
         {
            $category = "";
         }

         $categories[$transaction->id] = array(
            'payee' => $transaction->payee,
            'amount' => money_format('%.2n', $transaction->amount),
            'date' => date('m/d/Y', $transaction->timestamp),
            'category' => $category,
            'modify' => array('data'=>$modifyButton),
            'delete' => array('data'=>$deleteButton),
         );
      }

      if (!empty($categories))
      {
         $form['transaction'] = array(
            '#type' => 'details',
            '#title' => t("Manage Transaction Entries"),
            '#open' => TRUE,
         );

         $header = array(
            'payee' => t('Payee'),
            'amount' => t('Amount'),
            'date' => t('Date'),
            'category' => t('Category'),
            'modify' => t('Modify'),
            'delete' => t('Delete'),
         );

         $form['transaction']['categories'] = array(
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $categories,
            '#multiple' => TRUE,
         );

         $form['transaction']['remove'] = array(
            '#type' => 'submit',
            '#value' => 'Delete Selected',
         );
      }

      return $form;
   }

   /**
    * Returns the transaction filter form
    *
    * @return array
    *    The transaction filter form
    */
   private function getTransactionFilterForm()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget');

      // Get the end time from the session variable
      $endTime = $tempstore->get('endTime');
      if($endTime == NULL)
      {
         $endTime = time();
         $tempstore->set('endTime', $endTime);
      }
      $endYear = date("Y", $endTime);


      // Get the start time from the session variable
      $startTime = $tempstore->get('startTime');
      if($startTime == NULL)
      {
         $startTime = strtotime(t("January 1 !year", array('!year' => $endYear)));
         $tempstore->set('startTime', $startTime);
      }

      $categorySelect = $this->getBudgetCategorySelect();
      $categorySelect['#multiple'] = TRUE;
      $defaultCategories = $tempstore->get('transaction_categories');
      if($defaultCategories == NULL)
      {
         $defaultCategories = array_keys($categorySelect['#options']);
         $tempstore->set('transaction_categories', $defaultCategories);
      }
      $categorySelect['#default_value'] = $defaultCategories;

      $form = array();

      // Create the filter form
      $form['filter'] = array(
         '#type' => 'details',
         '#title' => t('Filter Transactions'),
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

      $form['filter']['filterCategorySelect'] = $categorySelect;

      $taxDeductible = $tempstore->get('taxDeduct');
      if($taxDeductible == NULL)
      {
         $taxDeductible = 0;
         $tempstore->set('taxDeduct', $taxDeductible);
      }

      $form['filter']['taxDeduct'] = array(
         '#type' => 'checkbox',
         '#title' => t('Tax Deductible'),
         '#default_value' => $taxDeductible,
      );

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
}
