# Finance — guide for the accountant and for IT

## Start the books (once per hospital)
1. **Finance → Chart of accounts → Make the starter chart.** It adds a hospital chart of accounts, the account roles (which account each kind of posting uses) and a "Whole hospital" cost centre. It also maps each Billing category to a revenue account by its name (laboratory, radiology, pharmacy, beds, consultation...); check **Where things are posted** and change any row.
2. **Fiscal years and periods → Create a fiscal year.** Until a fiscal year exists nothing is posted.
3. Add **Bank, cash and wallets** accounts (a till, the bank, a mobile wallet, the card clearing account) and tell each which Billing payment method it receives (CASH, CARD, MFS...).
4. Add **Tax codes** with the rates the accountant confirms.
5. Enter the **opening balances** as one journal (kind "opening"), approved by a second person.
6. Run `php artisan finance:post --days=365` once to bring in the Billing and stock history of the last year; after that the scheduler does it every ten minutes.

## Every day
- **Overview** shows cash and bank, what is owed to the hospital and by it, this month's result, postings that failed and journals waiting.
- **Posting problems** lists anything that could not be posted, with the reason (a locked period, an account not set). Fix the cause and press *Look for missed entries and try again*.
- **Banking the tills**: bank each closed cashier session; **Bank statements**: import the bank's CSV (date, description, reference, money in, money out), press *Match*, book the rest (bank charges, interest), then mark the statement reconciled.
- **Pay suppliers**: choose the supplier and invoices, enter the tax withheld (the accountant's figure), prepare; a second person approves; then pay.

## Month end
Check the Billing-vs-books check shows "in balance" → run depreciation → post the doctors' fee shares → look at the trial balance and statements → lock the month. At year end: **Fiscal years → Close the year** (it lists what still blocks it).

## Read-only API
`GET /api/v1/finance/{trial-balance|income-statement|balance-sheet|receivables|payables|reconciliation}` with a staff token (`Authorization: Bearer ...`) and the header `X-Organization-Id: <hospital id>` (the user must belong to it; a query parameter is ignored). Dates: `from`, `to` (`on` for the balance sheet). Answer: `{success, message, data, meta}`. Each endpoint needs its own permission (`finance.ledger.view`, `finance.report.view`, `finance.receivable.view`, `finance.payable.view`, `finance.posting.view`).

## Commands
`finance:post [--organization=ID] [--days=N]` · `finance:check`. Both are scheduled.
