<?php

namespace Drupal\budget\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the AccountForm form controller.
 *
 * This example demonstrates a simple form with a singe text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class AccountForm extends FormBase 
{
   public static function getAccountContents($entry = array())
   {
      $query = db_select('accounts', 't')
         ->fields('t', array('id', 'uid', 'name', 'balance'))
         ->orderby('name')
         ->execute();

      return $query;
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
      $form['account'] = array();
      $form['account'][] = $this->getAccountAddForm();
      $form['account'][] = $this->getAccountManageForm();

      return $form;
   }

   /**
    * Getter method for Form ID.
    *
    * The form ID is used in implementations of hook_form_alter() to allow other
    * modules to alter the render array built by this form controller.  it must
    * be unique site wide. It normally starts with the providing module's name.
    *
    * @return string
    *   The unique ID of the form defined by this class.
    */
   public function getFormId() 
   {
      return 'account_form';
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
         else if(strcasecmp($key, "name") == 0)
         {
            $name = $value;
         }
         else if(strcasecmp($key, "balance") == 0)
         {
            $balance = $value;
         }
      }

      if(!$operation)
      {
         $form_state->setErrorByName(NULL,
            'No Operation selected');
      }
      else if(strcasecmp($operation, "Add Account") == 0)
      {
         $this->addAccountValidate($form_state, $name, $balance);
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
         else if(strcasecmp($key, "name") == 0)
         {
            $name = $value;
         }
         else if(strcasecmp($key, "balance") == 0)
         {
            $balance = $value;
         }
         else if(strcasecmp($key, "categories") == 0)
         {
            $categories = $value;
         }
      }

      if(strcasecmp($operation, "Add Account") == 0)
      {
         $this->addAccountSubmit($name, $balance);
      }
      else if(strcasecmp($operation, "Delete Selected") == 0)
      {
         $this->removeAccountSubmit($categories);
      }
   }

   /**
    * Custom validation function for validating an account add.
    *
    * @param array $form
    *   The render array of the add form.
    * @param FormStateInterface $form_state
    *   Object describing the current state of the form.
    */
   public function addAccountValidate(FormStateInterface $form_state, string $name, string $balance)
   {
      if(!$name)
      {
         $form_state->setErrorByName('name', 
            'Please fill in the name field');
      }

      if(!$balance)
      {
         $form_state->setErrorByName('balance', 
            'Please fill in the balance field');
      }

      // Validate the balance is formatted properly
      if(preg_match('/^[+-]?[0-9]{1,3}(?:,?[0-9]{3})*(?:\.[0-9]{1,2})?$/', $balance) != 1)
      {
         $form_state->setErrorByName('balance', 
            'Please enter a valid balance for the account');
      }

   }

   /**
    * Custom function for submitting an account add.
    */
   public function addAccountSubmit(string $name, string $balance)
   {
      $uid = \Drupal::currentUser()->id();
      $balance = str_replace('$', '', $balance);
      $balance = preg_replace("/([^0-9\\.-])/i", "", $balance);

      try
      {
         db_insert('accounts')
            ->fields(array(
               'uid' => $uid,
               'name' => $name,
               'balance' => $balance,
            ))
            ->execute();

         setlocale(LC_MONETARY, 'en_US.UTF-8');
         $debug_message = "Successfully added account " . $name . " with a balance of " . money_format('%.2n', $balance);
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
    * Custom function for removing an account.
    */
   public function removeAccountSubmit(array &$selectedIDs)
   {
      foreach($selectedIDs as $key => $value)
      {
         if($value)
         {
            // Get the account name from the database with this ID
            $name = db_select('accounts', 'a')
               ->fields('a', array('name'))
               ->condition('id', $key)
               ->execute()
               ->fetchField();

            $num_deleted = db_delete('accounts')
               ->condition('id', $key)
               ->execute();

            if($num_deleted > 0)
            {
               drupal_set_message('Successfully removed ' . $name);
            }
         }
      }
   }

   /**
    * Returns the accounts add form
    */
   private function getAccountAddForm()
   {
      $form = array();

      // Create the addAcount form
      $form['addAccount'] = array(
         '#type' => 'fieldset',
         '#title' => t('Add a new account'),
         '#collapsible' => TRUE,
         '#collapsed' => FALSE,
      );

      $form['addAccount']['name'] = array(
         '#type' => 'textfield',
         '#title' => t('Account Name'),
         '#size' => 40,
         '#maxLength' => 40,
      );

      $form['addAccount']['balance'] = array(
         '#type' => 'textfield',
         '#title' => t('Balance'),
         '#size' => 40,
         '#maxLength' => 40,
      );

      $form['addAccount']['submit'] = array(
         '#type' => 'submit',
         '#value' => 'Add Account',
      );

      return $form;
   }

   /**
    * Returns the account manaagment form
    */
   function getAccountManageForm()
   {
      $form = array();

      $result = $this->getAccountContents();
      $categories = array();

      // Iterate over the accounts and format as strings
      setlocale(LC_MONETARY, 'en_US.UTF-8');
      foreach ($result as $account)
      {
         $categories[$account->id] = array(
            'name' => $account->name,
            'balance' => money_format('%.2n', $account->balance),
         );
      }

      if (!empty($categories))
      {
         $form['accounts'] = array(
            '#type' => 'fieldset',
            '#title' => t("Manage Accounts"),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
         );

         $header = array(
            'name' => t('Account Name'),
            'balance' => t('Account Balance'),
         );

         $form['accounts']['categories'] = array(
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $categories,
            '#multiple' => TRUE,
         );

         $form['accounts']['remove'] = array(
            '#type' => 'submit',
            '#value' => 'Delete Selected',
         );
      }

      return $form;
   }
}
