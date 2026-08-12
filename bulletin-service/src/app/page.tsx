export default function Home() {
  return (
    <div style={{ padding: "40px", fontFamily: "Arial, sans-serif", maxWidth: "700px", margin: "0 auto" }}>
      <h1>Bulletin Service - CPB Douala</h1>
      <p style={{ color: "#666" }}>Service de génération rapide de bulletins scolaires (Puppeteer).</p>
      <hr />
      <h3>Endpoints:</h3>
      <ul style={{ lineHeight: 2 }}>
        <li><code>POST /api/bulletins/generate-class</code> — Bulletins de toute la classe (PDF fusionné)</li>
        <li><code>POST /api/bulletins/generate-single</code> — Bulletin individuel (PDF)</li>
        <li><code>POST /api/bulletins/preview</code> — Prévisualisation HTML</li>
        <li><code>GET /api/health</code> — Statut du service</li>
      </ul>
    </div>
  );
}
