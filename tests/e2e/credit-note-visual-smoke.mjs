import fs from 'node:fs/promises'
import path from 'node:path'
import process from 'node:process'
import { chromium } from 'playwright'

const baseUrl = process.env.E2E_BASE_URL || 'http://127.0.0.1:8000'
const outputDir = process.env.E2E_OUTPUT_DIR || 'artifacts/visual-smoke'
const email = process.env.E2E_EMAIL || 'admin@autofacture.local'
const password = process.env.E2E_PASSWORD || 'autofacture-dev'
const invoiceNumber = process.env.E2E_INVOICE_NUMBER || 'FAC-VISUEL-000001'

await fs.mkdir(outputDir, { recursive: true })

const browser = await chromium.launch({ headless: true })
const context = await browser.newContext({
  viewport: { width: 1440, height: 1000 },
  locale: 'fr-FR',
  colorScheme: 'light',
})
const page = await context.newPage()
page.setDefaultTimeout(20_000)
page.setDefaultNavigationTimeout(30_000)

const report = {
  started_at: new Date().toISOString(),
  base_url: baseUrl,
  invoice_number: invoiceNumber,
  steps: [],
  console_errors: [],
  page_errors: [],
  failed_requests: [],
}

page.on('console', (message) => {
  if (message.type() === 'error') {
    report.console_errors.push(message.text())
  }
})
page.on('pageerror', (error) => report.page_errors.push(error.message))
page.on('requestfailed', (request) => {
  report.failed_requests.push({
    url: request.url(),
    failure: request.failure()?.errorText || 'unknown',
  })
})

async function screenshot(name, fullPage = true) {
  const file = path.join(outputDir, name)
  await page.screenshot({ path: file, fullPage })
  report.steps.push({ name, url: page.url(), screenshot: file })
}

async function waitForText(text) {
  await page.getByText(text, { exact: false }).first().waitFor({ state: 'visible' })
}

async function openInvoiceActions() {
  const buttons = page.locator('button:visible')
  const count = await buttons.count()

  for (let index = count - 1; index >= 0; index -= 1) {
    const button = buttons.nth(index)

    try {
      await button.click({ timeout: 1_500 })

      if (await page.getByText('Créer un avoir', { exact: true }).isVisible()) {
        return
      }

      await page.keyboard.press('Escape')
    } catch {
      // Le bouton essayé n'est pas le menu d'actions de la facture.
    }
  }

  throw new Error('Le menu d’actions de la facture n’a pas été trouvé.')
}

try {
  await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' })
  await screenshot('01-login.png')

  await page.locator('input[name="email"]').fill(email)
  await page.locator('input[name="password"]').fill(password)
  await Promise.all([
    page.waitForURL(/\/admin\/dashboard/),
    page.locator('#loginForm button[type="submit"]').click(),
  ])
  await waitForText('Tableau de bord')
  await screenshot('02-dashboard.png')

  await page.goto(`${baseUrl}/admin/invoices`, { waitUntil: 'networkidle' })
  await waitForText(invoiceNumber)
  await screenshot('03-invoices-list.png')

  await page.getByText(invoiceNumber, { exact: true }).first().click()
  await page.waitForURL(/\/admin\/invoices\/\d+\/view/)
  await waitForText(invoiceNumber)
  await page.locator('iframe').first().waitFor({ state: 'visible' })
  await screenshot('04-invoice-view.png')

  await openInvoiceActions()
  await waitForText('Créer un avoir')
  await screenshot('05-invoice-actions.png')

  await page.getByText('Créer un avoir', { exact: true }).click()
  await waitForText('Maximum disponible')
  await screenshot('06-credit-note-modal-total.png')

  await page.getByText('Avoir partiel', { exact: true }).click()
  const creditNoteForm = page.locator('form').filter({ hasText: 'Émettre et sceller l’avoir' })
  const amountInput = creditNoteForm.locator('input:not([type="radio"]):visible').last()
  await amountInput.fill('300')
  await creditNoteForm.locator('textarea').fill('Remise commerciale validée après contrôle contradictoire.')
  await screenshot('07-credit-note-modal-partial.png')

  await Promise.all([
    page.waitForURL(/\/admin\/credit-notes\/\d+\/view/),
    creditNoteForm.getByText('Émettre et sceller l’avoir', { exact: true }).click(),
  ])
  await waitForText('Émis et scellé')
  await waitForText('Remise commerciale validée')
  await page.locator('iframe').first().waitFor({ state: 'visible' })
  await screenshot('08-credit-note-view.png')

  const pdfLink = page.getByRole('link', { name: /Ouvrir le PDF/ })
  const pdfHref = await pdfLink.getAttribute('href')

  if (!pdfHref) {
    throw new Error('Le lien du PDF de l’avoir est absent.')
  }

  const pdfResponse = await context.request.get(new URL(pdfHref, baseUrl).toString())

  if (!pdfResponse.ok()) {
    throw new Error(`Le PDF de l’avoir répond ${pdfResponse.status()}.`)
  }

  const contentType = pdfResponse.headers()['content-type'] || ''

  if (!contentType.includes('application/pdf')) {
    throw new Error(`Le document retourné n’est pas un PDF (${contentType}).`)
  }

  await fs.writeFile(path.join(outputDir, 'credit-note.pdf'), await pdfResponse.body())

  await page.goto(`${baseUrl}/admin/credit-notes`, { waitUntil: 'networkidle' })
  await waitForText(invoiceNumber)
  await waitForText('Imputé sur la facture')
  await page.getByText(/^AV-\d+$/).first().waitFor({ state: 'visible' })
  await screenshot('09-credit-notes-list.png')

  await page.setViewportSize({ width: 390, height: 844 })
  await screenshot('10-credit-notes-list-mobile.png')

  await page.getByText('Consulter l’avoir', { exact: true }).click()
  await page.waitForURL(/\/admin\/credit-notes\/\d+\/view/)
  await waitForText('Émis et scellé')
  await screenshot('11-credit-note-view-mobile.png')

  report.completed_at = new Date().toISOString()
  report.status = 'success'
} catch (error) {
  report.completed_at = new Date().toISOString()
  report.status = 'failure'
  report.error = error instanceof Error ? error.stack : String(error)
  await screenshot('99-failure.png').catch(() => {})
  throw error
} finally {
  await fs.writeFile(
    path.join(outputDir, 'visual-report.json'),
    JSON.stringify(report, null, 2)
  )
  await context.close()
  await browser.close()
}
