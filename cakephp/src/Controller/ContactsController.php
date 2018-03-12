<?php

namespace App\Controller;

use App\Form\ContactForm;

class ContactsController extends AppController
{
  public function index()
  {
    $contact = new ContactForm();

    if ($this->request->is('post')) {
      if ($contact->execute($this->request->data)) {
        $this->Flash->success('メール送信しました');
      } else {
        $this->Flash->error('バリデーションに引っかかりました。');
      }
    }

    $this->set('contact', $contact);
  }
}