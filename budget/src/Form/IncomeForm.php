<?php

namespace Drupal\budget\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Date;

/**
 * Implements the IncomeForm form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class IncomeForm extends FormBase
{
   /** Returns the income category select array to add in forms
    *
    * @param string $defaultCategory
    *    Selected category to return
    * @return array
    *    Category select array
    */
   private function getIncomeCategorySelect(string &$defaultCategory=NULL)
   {
      $categorySelect = array();

      // Get the income contents from the database
      $result = IncomeCategoryForm::getIncomeCategoryContents($category);

      $categories = array();
      foreach($result as $incomeCategory)
      {
         $categories[$incomeCategory->id] = $incomeCategory->category;
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
    * Returns the contents of the income table in the database.
    *
    * If the id is provided, then the contents for that id are returned.
    * Otherwise, the entire income database table is returned
    *
    * @param string $startTime
    *    The start time of the income to return
    * @param string $endTime
    *    The end time of the income to return
    * @return array
    *    Income contents from the database
    */
   public static function getIncomeContents(string &$id=NULL, string &$startTime=NULL, string &$endTime=NULL)
   {
      if($id != NULL)
      {
         $query = db_select('income', 'i')
            ->fields('i', array('id', 'uid', 'timestamp', 'amount', 'category', 'notes'))
            ->condition('id', $id)
            ->execute();
      }
      else if($startTime && $endTime)
      {
         $query = db_select('income', 'i')
            ->fields('i', array('id', 'uid', 'timestamp', 'amount', 'category', 'notes'))
            ->condition('timestamp', array($startTime, $endTime), 'BETWEEN')
            ->orderby('timestamp', 'DESC')
            ->execute();
      }
      else
      {
         $query = db_select('income', 'i')
            ->fields('i', array('id', 'uid', 'timestamp', 'amount', 'category', 'notes'))
            ->orderby('timestamp', 'DESC')
            ->execute();
      }

      return $query;
   }

   /**
    * Returns the number of Income entries in the database
    *
    * @return int
    *    Number of income entries in the database
    */
   public static function getNumIncomes(string &$startTime=NULL, string &$endTime=NULL)
   {
      if($startTime && $endTime)
      {
         $count = db_select('income')
            ->fields(NULL, array('field'))
            ->condition('timestamp', array($startTime, $endTime), 'BETWEEN')
            ->countQuery()
            ->execute()
            ->fetchField();
      }
      else
      {
         $count = db_select('income')
            ->fields(NULL, array('field'))
            ->countQuery()
            ->execute()
            ->fetchField();
      }

      return intval($count);
   }

   /**
    * Build the income form.
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
      $form['income'] = array();

      if($form_state->has('page_num') &&
         $form_state->get('page_num') == 2)
      {
         $form['income'][] = $this->getIncomeAddUpdateForm($form_state->get('modifyId'));
      }
      else
      {
         $form['income'][] = $this->getIncomeAddUpdateForm();
         $form['income'][] = $this->getIncomeFilterForm();
         $form['income'][] = $this->getIncomeManageForm();
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
      return 'income_form';
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
         else if(strcasecmp($key, "notes") == 0)
         {
            $notes = $value;
         }
      }

      if(strcasecmp($operation, "Add Income") == 0)
      {
         $this->addUpdateIncomeValidate($form_state, $timestamp, $amount, $category, $notes);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         $this->addUpdateIncomeValidate($form_state, $timestamp, $amount, $category, $notes);
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
         else if(strcasecmp($key, "notes") == 0)
         {
            $notes = $value;
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

      if(strcasecmp($operation, "Add Income") == 0)
      {
         $this->addIncomeSubmit($timestamp, $amount, $category, $notes);
      }
      else if(strcasecmp($operation, "Delete Selected") == 0)
      {
         $this->removeSelectedIncomes($selectedIds);
      }
      else if(strcasecmp($operation, "Delete Individual") == 0)
      {
         $this->removeIncomeWithId($deleteId);
      }
      else if(strcasecmp($operation, "Modify Individual") == 0)
      {
         $form_state->set('page_num', 2)
            ->set('modifyId', $modifyId)
            ->setRebuild(TRUE);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         $this->updateIncomeSubmit($timestamp, $amount, $category, $notes, $form_state->get('modifyId'));
      }
      else if(strcasecmp($operation, "Filter") == 0)
      {
         $this->filterIncomeSubmit($startDate, $endDate);
      }
      else if(strcasecmp($operation, "Reset") == 0)
      {
         $this->filterResetSubmit();
      }
   }

   /**
    * Custom validation function for validating an income add or update
    *
    * @param FormStateInterface $form_state
    *    Object describing the current state of the form.
    * @param string $timestamp
    *    Timestamp of the income
    * @param string $amount
    *    Amount of the income
    * @param string $category
    *    Category for the income
    * @param string $notes
    *    Notes for the income
    */
   private function addUpdateIncomeValidate(FormStateInterface $form_state, string &$timestamp, string &$amount, string &$category, string &$notes)
   {
      if(!$timestamp)
      {
         $form_state->setErrorByName('date',
            'Please enter a valid date for the transaction');
      }

      // Validate the amount is formatted properly
      if(!$amount)
      {
         $form_state->setErrorByName('amount', 'Please enter the amount');
      }
      else if(preg_match('/^[+-]?[0-9]{1,3}(?:,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $amount) != 1)
      {
         $form_state->setErrorByName('amount',
            'Please enter a valid amount for the income');
      }

      // Validate that the category ID is in the database
      if(!$category)
      {
         $form_state->setErrorByName('categorySelect', 
            'Please select a category');
      }
      else
      {
         $result = IncomeCategoryForm::getIncomeCategoryContents($category)
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
      $incomeTime = strtotime($timestamp);

      if($incomeTime > $currentTime)
      {
         $form_state->setErrorByName('date', 'Date cannot be in the future');
      }
   }

   /**
    * Custom function for adding an income entry
    *
    * @param string $timestamp
    *    Timestamp of the income
    * @param string $amount
    *    Amount of the income
    * @param string $category
    *    Category for the income
    * @param string $notes
    *    Notes for the income
    */
   private function addIncomeSubmit(string &$timestamp, string &$amount, string &$category, string &$notes)
   {
      $uid = \Drupal::currentUser()->id();
      $amount = str_replace('$', '', $amount);
      $amount = preg_replace("/([^0-9\\.-])/i", "", $amount);

      $currentTime = strtotime($timestamp);

      try
      {
         db_insert('income')
            ->fields(array(
               'uid' => $uid,
               'timestamp' => $currentTime,
               'amount' => $amount,
               'category' => $category,
               'notes' => $notes,
            ))
            ->execute();

         setlocale(LC_MONETARY, 'en_US.UTF-8');
         $debug_message = "Successfully added income " . money_format('%.2n', $amount) . " on " . $timestamp;
         drupal_set_message($debug_message, 'status');
      }
      catch (\Exception $e)
      {
         $debug_message = 'db_insert failed. Message=' . $e->getMessage();
         $debug_message .= ', Query=' . $e->query_string;
         drupal_set_message($debug_message, 'error');
      }
   }

   private function filterIncomeSubmit(string &$startDate, string &$endDate)
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget_income');

      // Get the end time from the session variable
      $endTime = strtotime($endDate);
      $tempstore->set('endTime', $endTime);

      // Get the start time from the session variable
      $startTime = strtotime($startDate);
      $tempstore->set('startTime', $startTime);
   }

   private function filterResetSubmit()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget_income');
      $endTime = time();
      $tempstore->set('endTime', $endTime);
      $endYear = date("Y", $endTime);

      // Get the start time from the session variable
      $startTime = strtotime(t("1/1/" . $endYear));
      $tempstore->set('startTime', $startTime);
   }

   /**
    * Custom function for updating an income entry
    *
    * @param string $timestamp
    *    Timestamp of the income
    * @param string $amount
    *    Amount of the income
    * @param string $category
    *    Category for the income
    * @param string $notes
    *    Notes for the income
    * @param string $id
    *    ID of the income entry to update
    */
   private function updateIncomeSubmit(string &$timestamp, string &$amount, string &$category, string &$notes, string &$id)
   {
      if($id)
      {
         $amount = str_replace('$', '', $amount);
         $amount = preg_replace("/([^0-9\\.-])/i", "", $amount);

         $currentTimestamp = strtotime($timestamp);

         try
         {
            db_update('income')
               ->fields(array(
                  'timestamp' => $currentTimestamp,
                  'amount' => $amount,
                  'category' => $category,
                  'notes' => $notes,
               ))
               ->condition('id', $id)
               ->execute();

            setlocale(LC_MONETARY, 'en_US.UTF-8');
            $debug_message = "Successfully updated income " . money_format('%.2n', $amount) . " on " . $timestamp;
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
    * Custom function for removing all selected incomes from the database
    *
    * @param array selectedIDs
    *    Array of the selected IDs from the income table
    */
   private function removeSelectedIncomes(array &$selectedIDs)
   {
      foreach($selectedIDs as $key => $value)
      {
         if($value)
         {
            $this->removeIncomeWithId($key);
         }
      }
   }

   /**
    * Remove an income from the database
    *
    * @param string id
    *    ID of the income to remove
    */
   private function removeIncomeWithId(string &$id)
   {
      $num_deleted = db_delete('income')
         ->condition('id', $id)
         ->execute();
   }

   /**
    * Returns the income add form
    *
    * Builds either the income add form or the income update form. If the
    * input parameter $modifyId is provided, then the income update form
    * will be returned. The modifyId is the ID of the income in the database
    * that will be updated. The input fields are pre-populated with
    * the values from the database.
    *
    * @param string $modifyId
    *    ID field from the database of the income to modify
    * @return array
    *    The income add/update form
    */
   private function getIncomeAddUpdateForm(string &$modifyId=NULL)
   {
      $form = array();

      $categorySelect = $this->getIncomeCategorySelect();

      if(!empty($categorySelect))
      {
         $form['addIncome'] = array(
            '#type' => 'details',
            '#title' => t('Add a new income transaction'),
            '#open' => TRUE,
         );

         $form['addIncome']['amount'] = array(
            '#type' => 'textfield',
            '#title' => t('Amount'),
            '#size' => 40,
            '#maxLength' => 40,
         );

         $form['addIncome']['date'] = array(
            '#type' => 'date',
            '#title' => t('Date'),
            '#default_value' => t(date('Y-m-d')),
         );

         $form['addIncome']['categorySelect'] = $categorySelect;

         $form['addIncome']['notes'] = array(
            '#type' => 'textfield',
            '#title' => t('Notes'),
            '#size' => 40,
            '#maxLength' => 100,
         );

         if($modifyId)
         {
            $result = $this->getIncomeContents($modifyId);
            foreach($result as $income)
            {
               $categorySelect = $this->getIncomeCategorySelect($income->category);
               $form['addIncome']['amount']['#value'] = $income->amount;
               $form['addIncome']['date']['#value'] = t(date('Y-m-d', $income->timestamp));
               $form['addIncome']['categorySelect'] = $categorySelect;
               $form['addIncome']['notes']['#value'] = $income->notes;
               $form['addIncome']['#title'] = t("Update income");
               $form['addIncome']['#open'] = TRUE;
            }

            $form['addIncome']['submit'] = array(
               '#type' => 'submit',
               '#value' => 'Update',
            );

            $form['addIncome']['cancel'] = array(
               '#type' => 'submit',
               '#value' => 'Cancel',
            );
         }
         else
         {
            $form['addIncome']['submit'] = array(
               '#type' => 'submit',
               '#value' => 'Add Income',
            );
         }
      }
      else
      {
         drupal_set_message("Add at least one income category in order to add an income", 'error');
      }

      return $form;
   }

   /**
    * Returns the income manaagment form
    *
    * Returns a tableselect showing the values for all income entries  
    *
    * @return array
    *    The income management form
    */
   private function getIncomeManageForm()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget_income');

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

      $form = array();

      $id = NULL;
      $result = $this->getIncomeContents($id, $startTime, $endTime);

      $categories = array();

      // Iterate over the income entries and format as strings
      setlocale(LC_MONETARY, 'en_US.UTF-8');
      foreach ($result as $income)
      {
         $deleteButton = array(
            '#type' => 'submit',
            '#value' => t('Delete'),
            '#name' => t('delete_button_' . $income->id),
         );

         $modifyButton = array(
            '#type' => 'submit',
            '#value' => t('Modify'),
            '#name' => t('modify_button_' . $income->id),
         );

         if($income->notes)
         {
            $note = $income->notes;
         }
         else
         {
            $note = t("");
         }

         $categorySelect = $this->getIncomeCategorySelect($income->category);
         $category = $categorySelect['#options'][$income->category];
         if($category == null)
         {
            $category = "";
         }

         $categories[$income->id] = array(
            'amount' => money_format('%.2n', $income->amount),
            'date' => date('m/d/Y', $income->timestamp),
            'category' => $category,
            'notes' => $note,
            'modify' => array('data'=>$modifyButton),
            'delete' => array('data'=>$deleteButton),
         );
      }

      if (!empty($categories))
      {
         $form['income'] = array(
            '#type' => 'details',
            '#title' => t("Manage Income Entries"),
            '#open' => TRUE,
         );

         $header = array(
            'amount' => t('Amount'),
            'date' => t('Date'),
            'category' => t('Category'),
            'notes' => t('Notes'),
            'modify' => t('Modify'),
            'delete' => t('Delete'),
         );

         $form['income']['categories'] = array(
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $categories,
            '#multiple' => TRUE,
         );

         $form['income']['remove'] = array(
            '#type' => 'submit',
            '#value' => 'Delete Selected',
         );
      }

      return $form;
   }

   /**
    * Returns the income filter form
    *
    * @return array
    *    The income filter form
    */
   private function getIncomeFilterForm()
   {
      $tempstore = \Drupal::service('user.private_tempstore')->get('budget_income');

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
