<?php

namespace Drupal\budget\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the CategoryForm form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class BudgetCategoryForm extends FormBase
{
   /**
    * Returns the contents of the budget category table in the database.
    *
    * If the id is provided, then the contents for that id are returned.
    * Otherwise, the entire budget category database table is returned
    *
    * @param string $id
    *    The ID of the budget category to return
    * @return array
    *    Budget category contents from the database
    */
   public static function getBudgetCategoryContents(string $id=NULL)
   {
      if($id)
      {
         $query = db_select('budgetCategories', 'b')
            ->fields('b', array('id', 'uid', 'category', 'allocation'))
            ->orderby('category')
            ->condition('id', $id)
            ->execute();
      }
      else
      {
         $query = db_select('budgetCategories', 'b')
            ->fields('b', array('id', 'uid', 'category', 'allocation'))
            ->orderby('category')
            ->execute();
      }

      return $query;
   }

   /**
    * Returns the number of budget categories in the database
    *
    * @return int
    *    Number of budget categories in the database
    */
   public static function getNumBudgetCategories()
   {
      $count = db_select('budgetCategories')
         ->fields(NULL, array('field'))
         ->countQuery()
         ->execute()
         ->fetchField();

      return intval($count);
   }

   /**
    * Build the simple form.
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
      $form['categories'] = array();

      if($form_state->has('page_num'))
      {
         if($form_state->get('page_num') == 2)
         {
            $form['categories'][] = $this->getBudgetCategoryAddUpdateForm($form_state->get('modifyId'));
         }
      }
      else
      {
         $form['categories'][] = $this->getBudgetCategoryAddUpdateForm();
         $form['categories'][] = $this->getBudgetCategoryManageForm();
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
      return 'budget_category_form';
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
         else if(strcasecmp($key, "budgetCategory") == 0)
         {
            $budgetCategory = $value;
         }
         else if(strcasecmp($key, "budgetAllocation") == 0)
         {
            $allocation = $value;
         }
      }

      if(strcasecmp($operation, "Add") == 0)
      {
         $this->addUpdateBudgetCategoryValidate($form_state, $budgetCategory, $allocation);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         $this->addUpdateBudgetCategoryValidate($form_state, $budgetCategory, $allocation);
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
         else if(strcasecmp($key, "budgetCategory") == 0)
         {
            $budgetCategory = $value;
         }
         else if(strcasecmp($key, "budgetAllocation") == 0)
         {
            $allocation = $value;
         }
         else if(strcasecmp($key, "categories") == 0)
         {
            $selectedIds = $value;
         }
         else if(strcasecmp(substr($key,0,14), "delete_budget_") == 0)
         {
            $operation = "Delete Budget Category";
            $deleteId = substr($key,strrpos($key,'_')+1);
         }
         else if(strcasecmp(substr($key,0,14), "modify_budget_") == 0)
         {
            $operation = "Modify Budget Category";
            $modifyId = substr($key,strrpos($key,'_')+1);
         }
      }

      if(strcasecmp($operation, "Add") == 0)
      {
         $this->addBudgetCategorySubmit($budgetCategory, $allocation);
      }
      else if(strcasecmp($operation, "Delete Selected") == 0)
      {
         $this->removeSelectedBudgetCategories($selectedIds);
      }
      else if(strcasecmp($operation, "Delete Budget Category") == 0)
      {
         $this->removeBudgetCategoryWithId($deleteId);
      }
      else if(strcasecmp($operation, "Modify Budget Category") == 0)
      {
         $form_state->set('page_num', 2)
            ->set('modifyId', $modifyId)
            ->setRebuild(TRUE);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         $this->updateBudgetCategorySubmit($budgetCategory, $allocation, $form_state->get('modifyId'));
      }
   }

   /**
    * Custom validation function for validating an budget category add or update
    *
    * @param FormStateInterface $form_state
    *   Object describing the current state of the form.
    * @param string $category
    *    Category of the budget to validate
    * @param string $allocation
    *    Allocation amount of the budget category to validate
    */
   private function addUpdateBudgetCategoryValidate(FormStateInterface $form_state, string &$category, string &$allocation)
   {
      if(!$category)
      {
         $form_state->setErrorByName('budgetCategory', 
            'Please fill in the category field');
      }

      if(!$allocation)
      {
         $form_state->setErrorByName('budgetAllocation', 
            'Please fill in the allocation field');
      }

      // Validate the allocation is formatted properly
      if(preg_match('/^[+-]?[0-9]{1,3}(?:,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $allocation) != 1)
      {
         $form_state->setErrorByName('budgetAllocation',
            'Please enter a valid allocation amount for the budget category');
      }
   }

   /**
    * Custom function for adding a budget category
    *
    * @param string category
    *    Category name to use for the category
    * @param string allocation
    *    Allocation amount to use for the budget category
    */
   private function addBudgetCategorySubmit(string &$category, string &$allocation)
   {
      $uid = \Drupal::currentUser()->id();
      $allocation = str_replace('$', '', $allocation);
      $allocation = preg_replace("/([^0-9\\.-])/i", "", $allocation);

      try
      {
         db_insert('budgetCategories')
            ->fields(array(
               'uid' => $uid,
               'category' => $category,
               'allocation' => $allocation,
            ))
            ->execute();

         setlocale(LC_MONETARY, 'en_US.UTF-8');
         $debug_message = "Successfully added budget category " . $category . " with an allocation amount of " . money_format('%.2n', $allocation);
         drupal_set_message($debug_message, 'status');
      }
      catch (\Exception $e)
      {
         $debug_message = 'db_insert failed. Message=' . $e->getMessage();
         $debug_message .= ', Query=' . $e->query_string;
         drupal_set_message($debug_message, 'error');
      }
   }

   /**
    * Custom function for updating a budget category
    *
    * @param string $category
    *    Name to use for the budget category
    * @param string $allocation
    *    Allocation amount to use for the budget category
    * @param string $id
    *    ID of the category to update
    */
   private function updateBudgetCategorySubmit(string &$category, string &$allocation, string &$id)
   {
      if($id)
      {
         $allocation = str_replace('$', '', $allocation);
         $allocation = preg_replace("/([^0-9\\.-])/i", "", $allocation);

         try
         {
            db_update('budgetCategories')
               ->fields(array(
                  'category' => $category,
                  'allocation' => $allocation,
               ))
               ->condition('id', $id)
               ->execute();

            setlocale(LC_MONETARY, 'en_US.UTF-8');
            $debug_message = "Successfully updated budget category " . $category . " with an allocation amount " . money_format('%.2n', $allocation);
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
    * Custom function for removing all selected budget categories
    *
    * @param array selectedIDs
    *    Array of the selected IDs from the budgetCategories table
    */
   private function removeSelectedBudgetCategories(array &$selectedIDs)
   {
      foreach($selectedIDs as $key => $value)
      {
         if($value)
         {
            $this->removeBudgetCategoryWithId($key);
         }
      }
   }

   /**
    * Remove a budget category from the database
    *
    * @param string id
    *    ID of the category to remove
    */
   private function removeBudgetCategoryWithId(string &$id)
   {
      // Get the category from the database with this ID
      $category = db_select('budgetCategories', 'b')
         ->fields('b', array('category'))
         ->condition('id', $id)
         ->execute()
         ->fetchField();

      $num_deleted = db_delete('budgetCategories')
         ->condition('id', $id)
         ->execute();

      if($num_deleted > 0)
      {
         drupal_set_message('Successfully removed ' . $category);
      }
   }

   /**
    * Returns the budget category add/update form
    *
    * Builds either the budget category add or update form. If the
    * input parameter $modifyId is provided, then the category update form
    * will be returned. The modifyId is the ID of the category in the database
    * that will be updated. The cateogyr and allocation fields are pre 
    * populated with the values from the database.
    *
    * @param string $modifyId
    *    ID field from the database of the category to modify
    * @return array
    *    The budget category add/update form
    */
   private function getBudgetCategoryAddUpdateForm(string &$modifyId=NULL)
   {
      $form = array();

      // Create the budget category add/update form
      $form['budgetAddCategory'] = array(
         '#type' => 'details',
         '#title' => t('Add a new budget category'),
      );

      $form['budgetAddCategory']['budgetCategory'] = array(
         '#type' => 'textfield',
         '#title' => t('Budget Category'),
         '#size' => 40,
         '#maxLength' => 40,
      );

      $form['budgetAddCategory']['budgetAllocation'] = array(
         '#type' => 'textfield',
         '#title' => t('Allocation Amount'),
         '#size' => 40,
         '#maxLength' => 40,
      );

      if($modifyId)
      {
         $result = $this->getBudgetCategoryContents($modifyId);
         foreach($result as $budgetCategory)
         {
            $form['budgetAddCategory']['budgetCategory']['#value'] = $budgetCategory->category;
            $form['budgetAddCategory']['budgetAllocation']['#value'] = $budgetCategory->allocation;
            $form['budgetAddCategory']['#title'] = t("Update Budget Category");
            $form['budgetAddCategory']['#open'] = TRUE;
         }

         $form['budgetAddCategory']['submit'] = array(
            '#type' => 'submit',
            '#value' => 'Update',
         );

         $form['budgetAddCategory']['cancel'] = array(
            '#type' => 'submit',
            '#value' => 'Cancel',
         );
      }
      else
      {
         $count = $this->getNumBudgetCategories();

         if($count == 0)
         {
            $form['budgetAddCategory']['#open'] = TRUE;
         }

         $form['budgetAddCategory']['submit'] = array(
            '#type' => 'submit',
            '#value' => 'Add',
         );
      }
      return $form;
   }

   /**
    * Returns the budget category manaagment form
    *
    * Returns a tableselect showing the values for all categories
    *
    * @return array
    *    The budget category management form
    */
   private function getBudgetCategoryManageForm()
   {
      $form = array();

      $result = $this->getBudgetCategoryContents();
      $categories = array();

      // Iterate over the budget categories and format as strings
      setlocale(LC_MONETARY, 'en_US.UTF-8');
      foreach ($result as $budgetCategory)
      {
         $deleteButton = array(
            '#type' => 'submit',
            '#value' => t('Delete'),
            '#name' => t('delete_budget_' . $budgetCategory->id),
         );

         $modifyButton = array(
            '#type' => 'submit',
            '#value' => t('Modify'),
            '#name' => t('modify_budget_' . $budgetCategory->id),
         );

         $categories[$budgetCategory->id] = array(
            'category' => $budgetCategory->category,
            'allocation' => money_format('%.2n', $budgetCategory->allocation),
            'modify' => array('data'=>$modifyButton),
            'delete' => array('data'=>$deleteButton),
         );
      }

      if (!empty($categories))
      {
         $form['budgetCategories'] = array(
            '#type' => 'details',
            '#title' => t("Manage Budget Categories"),
            '#open' => TRUE,
         );

         $header = array(
            'category' => t('Budget Category'),
            'allocation' => t('Allocation'),
            'modify' => t('Modify'),
            'delete' => t('Delete'),
         );

         $form['budgetCategories']['categories'] = array(
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $categories,
            '#multiple' => TRUE,
         );

         $form['budgetCategories']['remove'] = array(
            '#type' => 'submit',
            '#value' => 'Delete Selected',
         );
      }

      return $form;
   }
}
