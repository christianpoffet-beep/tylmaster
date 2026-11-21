<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Expense;

/**
 * Keeps the booking of an expense in line with the expense.
 *
 * - Paid in cash or privately (paid_from_account_id): the expense owns its
 *   booking, "expense account an Kasse/Eigenkapital", and rewrites it on
 *   every save.
 * - Paid by bank: the booking belongs to the bank movement. Only the expense
 *   account follows the expense; amount and date stay those of the bank.
 * - Neither: nothing is booked yet - the bank inbox will offer the expense
 *   when its payment comes in.
 */
class ExpenseBooker
{
    public function sync(Expense $expense): void
    {
        $expense->loadMissing('accounting', 'booking');
        if ($expense->accounting?->is_closed) return;

        $booking = $expense->booking;
        $bankBooking = $booking && $booking->bank_transaction_id;

        if ($expense->paid_from_account_id && !$bankBooking) {
            $data = [
                'accounting_id' => $expense->accounting_id,
                'booking_date' => $expense->expense_date->toDateString(),
                'description' => $expense->description,
                'debit_account_id' => $expense->account_id,
                'credit_account_id' => $expense->paid_from_account_id,
                'amount' => $expense->amount,
                'contact_id' => $expense->contact_id,
            ];

            if ($booking) {
                $booking->update($data);
            } else {
                $expense->update(['booking_id' => Booking::create($data)->id]);
            }

            return;
        }

        if ($booking && !$bankBooking) {
            // No longer paid directly: its own booking goes, the bank will settle it
            $booking->delete();
            $expense->refresh();

            return;
        }

        if ($bankBooking && $expense->account_id && (int) $booking->debit_account_id !== (int) $expense->account_id) {
            $booking->update(['debit_account_id' => $expense->account_id]);
        }
    }
}
