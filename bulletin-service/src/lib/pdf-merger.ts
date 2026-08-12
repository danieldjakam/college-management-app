import PDFMerger from "pdf-merger-js";

/**
 * Merge multiple PDF buffers into a single PDF
 */
export async function mergePdfs(pdfBuffers: Buffer[]): Promise<Buffer> {
  const merger = new PDFMerger();

  for (const buffer of pdfBuffers) {
    await merger.add(buffer);
  }

  const mergedBuffer = await merger.saveAsBuffer();
  return Buffer.from(mergedBuffer);
}
