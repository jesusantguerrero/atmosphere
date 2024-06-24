<?php

namespace Tests\Feature\Loan;

use App\Domains\Loans\Models\Loan;
use App\Domains\CRM\Enums\ClientStatus;
use Tests\Feature\Loan\Helpers\LoanBase;

class LoanTest extends LoanBase
{
  /**
   * A basic feature test example.
   *
   * @return void
   */
  public function testItShouldGetLoans()
  {
      $this->actingAs($this->user);

      $response = $this->get('/loans');

      $response->assertStatus(200);
  }

  public function testItShouldNotCreateLoanWithoutSourceAccountId() {
    $this->actingAs($this->user);

    $response = $this->post('/loans', array_merge(
    $this->loanData, [
      'source_account_id' => null
    ]));

    $response->assertSessionHasErrors(['source_account_id']);

  }

  public function testItShouldNotCreateLoanWithoutFunds() {
    $this->actingAs($this->user);

    $response = $this->withoutExceptionHandling()->post('/loans', $this->loanData);
    $response->assertSessionHasErrors();
  }

  public function testItShouldCreateLoan() {
    $this->actingAs($this->user);
    $this->fundAccount('daily_box', $this->loanData['amount'], $this->lender->team_id);
    $response = $this->post('/loans', $this->loanData);
    $response->assertStatus(302);
    $this->assertCount(1, Loan::all());
  }

  public function testLoanShouldHaveStatusPending() {
    $this->actingAs($this->user);
    $this->fundAccount('daily_box', $this->loanData['amount'], $this->lender->team_id);

    $response = $this->post('/loans', $this->loanData);
    $response->assertStatus(302);
    $loan = Loan::query()->first();

    $this->assertEquals($loan->payment_status, Loan::STATUS_PENDING);
  }

  public function testClientShouldHaveStatusActive() {
    $this->actingAs($this->user);
    $this->fundAccount('daily_box', $this->loanData['amount'], $this->lender->team_id);

    $response = $this->post('/loans', $this->loanData);
    $response->assertStatus(302);
    $loan = Loan::query()->first();

    $this->assertEquals($loan->client->status, ClientStatus::Active);
  }
}
