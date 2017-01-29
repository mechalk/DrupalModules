<?php

namespace Drupal\budget\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the CategoryForm form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class IncomeCategoryForm extends FormBase
{
   /**
    * Returns the contents of the income category table in the database.
    *
    * If the id is provided, then the contents for that id are returned.
    * Otherwise, the entire income category database table is returned
    *
    * @param string $id
    *    The ID of the income category information to return
    * @return array
    *    Income category contents from the database
    */
   public static function getIncomeCategoryContents(string $id=NULL)
   {
      if($id)
      {
         $query = db_select('incomeCategories', 'i')
            ->fields('i', array('id', 'uid', 'category'))
            ->orderby('category')
            ->condition('id', $id)
            ->execute();
      }
      else
      {
         $query = db_select('incomeCategories', 'i')
            ->fields('i', array('id', 'uid', 'category'))
            ->orderby('category')
            ->execute();
      }

      return $query;
   }

   /**
    * Returns the number of income categories in the database
    *
    * @return int
    *    Number of income categories in the database
    */
   public static function getNumIncomeCategories()
   {
      $count = db_select('incomeCategories')
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
            $form['categories'][] = $this->getIncomeCategoryAddUpdateForm($form_state->get('modifyId'));
         }
      }
      else
      {
         $form['categories'][] = $this->getIncomeCategoryAddUpdateForm();
         $form['categories'][] = $this->getIncomeCategoryManageForm();
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
      return 'income_category_form';
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
         else if(strcasecmp($key, "incomeCategory") == 0)
         {
            $incomeCategory = $value;
         }
      }

      if(strcasecmp($operation, "Add") == 0)
      {
         drupal_set_message("Add Income");
         $this->addUpdateIncomeCategoryValidate($form_state, $incomeCategory);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         drupal_set_message("Update Income");
         $this->addUpdateIncomeCategoryValidate($form_state, $incomeCategory);
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
         else if(strcasecmp($key, "incomeCategory") == 0)
         {
            $incomeCategory = $value;
         }
         else if(strcasecmp($key, "categories") == 0)
         {
            $selectedIds = $value;
         }
         else if(strcasecmp(substr($key,0,14), "delete_income_") == 0)
         {
            $operation = "Delete Income Category";
            $deleteId = substr($key,strrpos($key,'_')+1);
         }
         else if(strcasecmp(substr($key,0,14), "modify_income_") == 0)
         {
            $operation = "Modify Income Category";
            $modifyId = substr($key,strrpos($key,'_')+1);
         }
      }

      if(strcasecmp($operation, "Add") == 0)
      {
         $this->addIncomeCategorySubmit($incomeCategory);
      }
      else if(strcasecmp($operation, "Delete Selected") == 0)
      {
         $this->removeSelectedIncomeCategories($selectedIds);
      }
      else if(strcasecmp($operation, "Delete Income Category") == 0)
      {
         $this->removeIncomeCategoryWithId($deleteId);
      }
      else if(strcasecmp($operation, "Modify Income Category") == 0)
      {
         $form_state->set('page_num', 2)
            ->set('modifyId', $modifyId)
            ->setRebuild(TRUE);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         $this->updateIncomeCategorySubmit($incomeCategory, $form_state->get('modifyId'));
      }
   }

   /**
    * Custom validation function for validating an income category add or update
    *
    * @param FormStateInterface $form_state
    *   Object describing the current state of the form.
    * @param string $category
    *    Category of the income to validate
    */
   private function addUpdateIncomeCategoryValidate(FormStateInterface $form_state, string &$category)
   {
      if(!$category)
      {
         $form_state->setErrorByName('incomeCategory', 
            'Please fill in the category field');
      }
   }

   /**
    * Custom function for adding an income category
    *
    * @param string category
    *    Category name to use for the category
    */
   private function addIncomeCategorySubmit(string &$category)
   {
      $uid = \Drupal::currentUser()->id();

      try
      {
         db_insert('incomeCategories')
            ->fields(array(
               'uid' => $uid,
               'category' => $category,
            ))
            ->execute();

         setlocale(LC_MONETARY, 'en_US.UTF-8');
         $debug_message = "Successfully added income category " . $category;
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
    * Custom function for updating an income category
    *
    * @param string $category
    *    Name to use for the income category
    * @param string $id
    *    ID of the category to update
    */
   private function updateIncomeCategorySubmit(string &$category, string &$id)
   {
      if($id)
      {
         try
         {
            db_update('incomeCategories')
               ->fields(array(
                  'category' => $category,
               ))
               ->condition('id', $id)
               ->execute();

            setlocale(LC_MONETARY, 'en_US.UTF-8');
            $debug_message = "Successfully updated income category " . $category;
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
    * Custom function for removing all selected income categories
    *
    * @param array selectedIDs
    *    Array of the selected IDs from the incomeCategories table
    */
   private function removeSelectedIncomeCategories(array &$selectedIDs)
   {
      foreach($selectedIDs as $key => $value)
      {
         if($value)
         {
            $this->removeIncomeCategoryWithId($key);
         }
      }
   }

   /**
    * Remove an income category from the database
    *
    * @param string id
    *    ID of the category to remove
    */
   private function removeIncomeCategoryWithId(string &$id)
   {
      // Get the category from the database with this ID
      $category = db_select('incomeCategories', 'i')
         ->fields('i', array('category'))
         ->condition('id', $id)
         ->execute()
         ->fetchField();

      $num_deleted = db_delete('incomeCategories')
         ->condition('id', $id)
         ->execute();

      if($num_deleted > 0)
      {
         drupal_set_message('Successfully removed ' . $category);
      }
   }

   /**
    * Returns the income category add/update form
    *
    * Builds either the income category add or update form. If the
    * input parameter $modifyId is provided, then the category update form
    * will be returned. The modifyId is the ID of the category in the database
    * that will be updated. The cateogyr and allocation fields are pre 
    * populated with the values from the database.
    *
    * @param string $modifyId
    *    ID field from the database of the category to modify
    * @return array
    *    The income category add/update form
    */
   private function getIncomeCategoryAddUpdateForm(string &$modifyId=NULL)
   {
      $form = array();

      // Create the income category add/update form
      $form['incomeAddCategory'] = array(
         '#type' => 'details',
         '#title' => t('Add a new income category'),
      );

      $form['incomeAddCategory']['incomeCategory'] = array(
         '#type' => 'textfield',
         '#title' => t('Income Category'),
         '#size' => 40,
         '#maxLength' => 40,
      );

      if($modifyId)
      {
         $result = $this->getIncomeCategoryContents($modifyId);
         foreach($result as $incomeCategory)
         {
            $form['incomeAddCategory']['incomeCategory']['#value'] = $incomeCategory->category;
            $form['incomeAddCategory']['#title'] = t("Update Income Category");
            $form['incomeAddCategory']['#open'] = TRUE;
         }

         $form['incomeAddCategory']['submit'] = array(
            '#type' => 'submit',
            '#value' => 'Update',
         );

         $form['incomeAddCategory']['cancel'] = array(
            '#type' => 'submit',
            '#value' => 'Cancel',
         );
      }
      else
      {
         $count = $this->getNumIncomeCategories();

         if($count == 0)
         {
            $form['incomeAddCategory']['#open'] = TRUE;
         }

         $form['incomeAddCategory']['submit'] = array(
            '#type' => 'submit',
            '#value' => 'Add',
         );
      }
      return $form;
   }

   /**
    * Returns the income category manaagment form
    *
    * Returns a tableselect showing the values for all categories
    *
    * @return array
    *    The income category management form
    */
   private function getIncomeCategoryManageForm()
   {
      $form = array();

      $result = $this->getIncomeCategoryContents();
      $categories = array();

      // Iterate over the income categories and format as strings
      setlocale(LC_MONETARY, 'en_US.UTF-8');
      foreach ($result as $incomeCategory)
      {
         $deleteButton = array(
            '#type' => 'submit',
            '#value' => t('Delete'),
            '#name' => t('delete_income_' . $incomeCategory->id),
         );

         $modifyButton = array(
            '#type' => 'submit',
            '#value' => t('Modify'),
            '#name' => t('modify_income_' . $incomeCategory->id),
         );

         $categories[$incomeCategory->id] = array(
            'category' => $incomeCategory->category,
            'modify' => array('data'=>$modifyButton),
            'delete' => array('data'=>$deleteButton),
         );
      }

      if (!empty($categories))
      {
         $form['incomeCategories'] = array(
            '#type' => 'details',
            '#title' => t("Manage Income Categories"),
            '#open' => TRUE,
         );

         $header = array(
            'category' => t('Income Category'),
            'modify' => t('Modify'),
            'delete' => t('Delete'),
         );

         $form['incomeCategories']['categories'] = array(
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $categories,
            '#multiple' => TRUE,
         );

         $form['incomeCategories']['remove'] = array(
            '#type' => 'submit',
            '#value' => 'Delete Selected',
         );
      }

      return $form;
   }
}
