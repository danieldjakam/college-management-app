import puppeteer, { Browser } from "puppeteer";

let browserInstance: Browser | null = null;

/**
 * Get or create a Puppeteer browser instance (singleton)
 */
async function getBrowser(): Promise<Browser> {
  if (browserInstance && browserInstance.connected) {
    return browserInstance;
  }

  browserInstance = await puppeteer.launch({
    headless: true,
    args: [
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-dev-shm-usage",
      "--disable-gpu",
      "--font-render-hinting=none",
    ],
  });

  return browserInstance;
}

/**
 * Generate a PDF buffer from HTML string
 */
export async function htmlToPdf(
  html: string,
  options?: {
    format?: "A4" | "A3";
    landscape?: boolean;
    margin?: {
      top?: string;
      right?: string;
      bottom?: string;
      left?: string;
    };
  }
): Promise<Buffer> {
  const browser = await getBrowser();
  const page = await browser.newPage();

  try {
    await page.setContent(html, {
      waitUntil: "domcontentloaded",
      timeout: 15000,
    });

    const pdfBuffer = await page.pdf({
      format: options?.format || "A4",
      landscape: options?.landscape || false,
      printBackground: true,
      margin: options?.margin || {
        top: "5mm",
        right: "5mm",
        bottom: "5mm",
        left: "5mm",
      },
      preferCSSPageSize: true,
    });

    return Buffer.from(pdfBuffer);
  } finally {
    await page.close();
  }
}

/**
 * Generate multiple PDFs from an array of HTML strings (parallel)
 */
export async function batchHtmlToPdf(
  htmlPages: string[],
  options?: {
    format?: "A4" | "A3";
    landscape?: boolean;
    margin?: {
      top?: string;
      right?: string;
      bottom?: string;
      left?: string;
    };
  }
): Promise<Buffer[]> {
  const browser = await getBrowser();

  // Process in parallel batches of 5 to avoid overloading
  const batchSize = 5;
  const results: Buffer[] = [];

  for (let i = 0; i < htmlPages.length; i += batchSize) {
    const batch = htmlPages.slice(i, i + batchSize);
    const batchResults = await Promise.all(
      batch.map(async (html) => {
        const page = await browser.newPage();
        try {
          await page.setContent(html, {
            waitUntil: "domcontentloaded",
            timeout: 15000,
          });

          const pdfBuffer = await page.pdf({
            format: options?.format || "A4",
            landscape: options?.landscape || false,
            printBackground: true,
            margin: options?.margin || {
              top: "5mm",
              right: "5mm",
              bottom: "5mm",
              left: "5mm",
            },
            preferCSSPageSize: true,
          });

          return Buffer.from(pdfBuffer);
        } finally {
          await page.close();
        }
      })
    );
    results.push(...batchResults);
  }

  return results;
}

/**
 * Cleanup browser instance
 */
export async function closeBrowser(): Promise<void> {
  if (browserInstance) {
    await browserInstance.close();
    browserInstance = null;
  }
}
