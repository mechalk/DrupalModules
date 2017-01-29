<?php

namespace Drupal\budget\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the AccountForm form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class AccountForm extends FormBase
{
   /**
    * Returns the contents of the accounts in the database.
    *
    * If the id is provided, then the contents for that id are returned.
    * Otherwise, the entire account database table is returned
    *
    * @param string $id
    *    The ID of the account information to return
    * @return array
    *    Account contents from the database
    */
   public static function getAccountContents(string $id=NULL)
   {
      if($id)
      {
         $query = db_select('accounts', 't')
            ->fields('t', array('id', 'uid', 'name', 'balance'))
            ->orderby('name')
            ->condition('id', $id)
            ->execute();
      }
      else
      {
         $query = db_select('accounts', 't')
            ->fields('t', array('id', 'uid', 'name', 'balance'))
            ->orderby('name')
            ->execute();
      }

      return $query;
   }

   /**
    * Returns the number of accounts in the database
    *
    * @return int
    *    Number of accounts in the database
    */
   public static function getNumAccounts()
   {
      $count = db_select('accounts')
         ->fields(NULL, array('field'))
         ->countQuery()
         ->execute()
         ->fetchField();

      return intval($count);
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

      if($form_state->has('page_num') &&
         $form_state->get('page_num') == 2)
      {
         $form['account'][] = $this->getAccountAddUpdateForm($form_state->get('modifyId'));
      }
      else
      {
         $form['account'][] = $this->getAccountAddUpdateForm();
         $form['account'][] = $this->getAccountManageForm();
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
         else if(strcasecmp($key, "categories") == 0)
         {
            $categories = $value;
         }
      }

      if(strcasecmp($operation, "Add Account") == 0)
      {
         $this->addUpdateAccountValidate($form_state, $name, $balance);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         $this->addUpdateAccountValidate($form_state, $name, $balance);
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

      if(strcasecmp($operation, "Add Account") == 0)
      {
         $this->addAccountSubmit($name, $balance);
      }
      else if(strcasecmp($operation, "Delete Selected") == 0)
      {
         $this->removeSelectedAccounts($selectedIds);
      }
      else if(strcasecmp($operation, "Delete Individual") == 0)
      {
         $this->removeAccountWithId($deleteId);
      }
      else if(strcasecmp($operation, "Modify Individual") == 0)
      {
         $form_state->set('page_num', 2)
            ->set('modifyId', $modifyId)
            ->setRebuild(TRUE);
      }
      else if(strcasecmp($operation, "Update") == 0)
      {
         $this->updateAccountSubmit($name, $balance, $form_state->get('modifyId'));
      }
   }

   /**
    * Custom validation function for validating an account add or update
    *
    * @param FormStateInterface $form_state
    *   Object describing the current state of the form.
    * @param string $name
    *    Name of the account to validate
    * @param string $balance
    *    Balance of the account to validate
    */
   private function addUpdateAccountValidate(FormStateInterface $form_state, string $name, string $balance)
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
    * Custom function for adding an account
    *
    * @param string name
    *    Name to use for the account
    * @param string balance
    *    Balance to use for the account
    */
   private function addAccountSubmit(string &$name, string &$balance)
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
    * Custom function for updating an account
    *
    * @param string $name
    *    Name to use for the account
    * @param string $balance
    *    Balance to use for the account
    * @param string $id
    *    ID of the account to update
    */
   private function updateAccountSubmit(string &$name, string &$balance, string &$id)
   {
      if($id)
      {
         $balance = str_replace('$', '', $balance);
         $balance = preg_replace("/([^0-9\\.-])/i", "", $balance);

         try
         {
            db_update('accounts')
               ->fields(array(
                  'name' => $name,
                  'balance' => $balance,
               ))
               ->condition('id', $id)
               ->execute();

            setlocale(LC_MONETARY, 'en_US.UTF-8');
            $debug_message = "Successfully updated account " . $name . " with a balance of " . money_format('%.2n', $balance);
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
    * Custom function for removing all selected accounts from the database
    *
    * @param array selectedIDs
    *    Array of the selected IDs from the account table
    */
   private function removeSelectedAccounts(array &$selectedIDs)
   {
      foreach($selectedIDs as $key => $value)
      {
         if($value)
         {
            $this->removeAccountWithId($key);
         }
      }
   }

   /**
    * Remove an account from the database
    *
    * @param string id
    *    ID of the account to remove
    */
   private function removeAccountWithId(string &$id)
   {
      // Get the account name from the database with this ID
      $name = db_select('accounts', 'a')
         ->fields('a', array('name'))
         ->condition('id', $id)
         ->execute()
         ->fetchField();

      $num_deleted = db_delete('accounts')
         ->condition('id', $id)
         ->execute();

      if($num_deleted > 0)
      {
         drupal_set_message('Successfully removed ' . $name);
      }
   }

   /**
    * Returns the accounts add form
    *
    * Builds eith the account add form or the account update form. If the
    * input parameter $modifyId is provided, then the account update form
    * will be returned. The modifyId is the ID of the account in the database
    * that will be updated. The name and balance fields are pre populated with
    * the values from the database.
    *
    * @param string $modifyId
    *    ID field from the database of the account to modify
    * @return array
    *    The account add form
    */
   private function getAccountAddUpdateForm(string &$modifyId=NULL)
   {
      $form = array();

      // Create the addAcount form
      $form['addAccount'] = array(
         '#type' => 'details',
         '#title' => t('Add a new account'),
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

      if($modifyId)
      {
         $result = $this->getAccountContents($modifyId);
         foreach($result as $account)
         {
            $form['addAccount']['name']['#value'] = $account->name;
            $form['addAccount']['balance']['#value'] = $account->balance;
            $form['addAccount']['#title'] = t("Update account");
            $form['addAccount']['#open'] = TRUE;
         }

         $form['addAccount']['submit'] = array(
            '#type' => 'submit',
            '#value' => 'Update',
         );

         $form['addAccount']['cancel'] = array(
            '#type' => 'submit',
            '#value' => 'Cancel',
         );
      }
      else
      {
         $count = $this->getNumAccounts();

         if($count == 0)
         {
            $form['addAccount']['#open'] = TRUE;
         }

         $form['addAccount']['submit'] = array(
            '#type' => 'submit',
            '#value' => 'Add Account',
         );
      }
      return $form;
   }

   /**
    * Returns the account manaagment form
    *
    * Returns a tableselect showing the values for all accounts in the database
    *
    * @return array
    *    The account management form
    */
   private function getAccountManageForm()
   {
      $form = array();

      $result = $this->getAccountContents();
      $categories = array();

      // Iterate over the accounts and format as strings
      setlocale(LC_MONETARY, 'en_US.UTF-8');
      foreach ($result as $account)
      {
         $deleteButton = array(
            '#type' => 'submit',
            '#value' => t('Delete'),
            '#name' => t('delete_button_' . $account->id),
         );

         $modifyButton = array(
            '#type' => 'submit',
            '#value' => t('Modify'),
            '#name' => t('modify_button_' . $account->id),
         );

         $categories[$account->id] = array(
            'name' => $account->name,
            'balance' => money_format('%.2n', $account->balance),
            'modify' => array('data'=>$modifyButton),
            'delete' => array('data'=>$deleteButton),
         );
      }

      if (!empty($categories))
      {
         $form['accounts'] = array(
            '#type' => 'details',
            '#title' => t("Manage Accounts"),
            '#open' => TRUE,
         );

         $header = array(
            'name' => t('Account Name'),
            'balance' => t('Account Balance'),
            'modify' => t('Modify'),
            'delete' => t('Delete'),
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
