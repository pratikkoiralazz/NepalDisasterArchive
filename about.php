<?php
require_once __DIR__ . '/config/config.php';
$lastUpdated = db()->query('SELECT MAX(updated_at) FROM stories')->fetchColumn();
$storyCount = (int)db()->query("SELECT COUNT(*) FROM stories WHERE status='PUBLISHED'")->fetchColumn();
$categories = db()->query('SELECT name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Learn about the Nepal Disaster Archive, its mission, features, sources, editorial approach, and project credit.">
    <title>About the Nepal Disaster Archive</title>
    <style>
        :root{--ink:#17202a;--paper:#f7f3ed;--red:#9e2b25;--green:#263d3a;--line:#d9d0c6;--muted:#59625f}
        *{box-sizing:border-box}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:Georgia,serif}
        .about-hero{padding:76px 7% 68px;background:var(--green);color:#fff}
        .about-hero-inner,.page{width:min(1080px,90%);margin:auto}
        .eyebrow{color:#a53b32;font:800 12px Arial,sans-serif;text-transform:uppercase;letter-spacing:1.3px}
        .about-hero .eyebrow{color:#e5b5a9}
        .about-hero h1{max-width:850px;font-size:clamp(44px,7vw,76px);line-height:.98;margin:16px 0 22px}
        .about-hero p{max-width:800px;color:#e0e7e2;font-size:20px;line-height:1.7;margin:0}
        .page{padding:55px 0 75px}
        .intro{max-width:850px;margin:0 0 42px;font-size:19px;line-height:1.8;color:#444}
        .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:0 0 48px}
        .stat{padding:20px;background:#fff;border:1px solid var(--line);border-radius:9px}
        .stat strong{display:block;color:var(--red);font:800 27px Arial,sans-serif}
        .stat span{display:block;margin-top:6px;color:var(--muted);font:14px/1.5 Arial,sans-serif}
        .section-heading{font-size:34px;line-height:1.15;margin:0 0 20px}
        .feature-grid,.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin:0 0 52px}
        .block{background:#fff;border:1px solid var(--line);border-radius:9px;padding:24px}
        .block h3{font-size:22px;margin:0 0 10px}
        .block p,.block li,.coverage>p{color:#505956;line-height:1.75}
        .block p{margin:0}
        .block ul{margin:12px 0 0;padding-left:21px}
        .block li+li{margin-top:5px}
        .coverage{padding:27px;background:#fff;border:1px solid var(--line);border-radius:9px;margin-bottom:52px}
        .category-list{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
        .category-list span{padding:8px 11px;border-radius:99px;background:#f2e9e1;color:#573b35;font:13px Arial,sans-serif}
        .ownership{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin:0 0 52px}
        .ownership .block{height:100%}
        .text-link{color:var(--red);font-weight:700;text-underline-offset:3px}
        .page-link{display:inline-block;margin-top:14px;color:var(--red);font:700 14px Arial,sans-serif;text-decoration:none}
        .page-link:hover{text-decoration:underline}
        .last-updated{padding-top:20px;border-top:1px solid var(--line);color:var(--muted);font:14px Arial,sans-serif}
        .footer{background:#111820;color:#d8dde0;padding:38px 7%;font-family:Arial,sans-serif}
        .footer-inner{width:min(1080px,90%);margin:auto}
        .footer strong{color:#fff;letter-spacing:.5px}
        .footer p{line-height:1.6}
        .social-links{display:flex;gap:14px;margin-top:16px}
        .social-links a{display:inline-flex;width:38px;height:38px;align-items:center;justify-content:center;border:1px solid #59636a;border-radius:50%;color:#fff}
        .social-links svg{width:19px;height:19px;fill:currentColor;stroke:currentColor;stroke-width:1.5}
        .social-links svg rect,.social-links svg circle{fill:none}
        @media(max-width:700px){.about-hero{padding:55px 0}.page{padding:38px 0 55px}.stats{grid-template-columns:1fr}.feature-grid,.detail-grid,.ownership{grid-template-columns:1fr}.section-heading{font-size:29px}}
    </style>
</head>
<body>
<?php require_once __DIR__ . '/includes/public-nav.php'; ?>
<header class="about-hero">
    <div class="about-hero-inner">
        <div class="eyebrow">About the project</div>
        <h1>Understanding Nepal's disaster history.</h1>
        <p>A public archive bringing disaster history, community experience, and practical preparedness resources together in one place.</p>
    </div>
</header>
<main class="page">
    <p class="intro">Disasters shape communities long after the immediate event. The Nepal Disaster Archive aims to make historical information easier to explore, preserve lived experience, and connect learning with preparedness. It is designed for the public, students, researchers, educators, journalists, and people working to understand disaster risk in Nepal.</p>

    <div class="stats" aria-label="Archive at a glance">
        <div class="stat"><strong><?= number_format($storyCount) ?></strong><span>published stories in the archive</span></div>
        <div class="stat"><strong><?= number_format(count($categories)) ?></strong><span>hazard categories for browsing</span></div>
        <div class="stat"><strong><?= e($lastUpdated ? date('F j, Y', strtotime((string)$lastUpdated)) : 'In progress') ?></strong><span>most recent story update</span></div>
    </div>

    <section aria-labelledby="mission-heading">
        <div class="eyebrow">Why this archive exists</div>
        <h2 class="section-heading" id="mission-heading">Our purpose</h2>
        <p class="intro">The project brings together structured disaster records and human perspectives so that past events are easier to find, compare, and learn from. By documenting sources and uncertainty, it supports informed discussion rather than presenting incomplete historical records as unquestionable facts.</p>
    </section>

    <section aria-labelledby="features-heading">
        <div class="eyebrow">What you can do here</div>
        <h2 class="section-heading" id="features-heading">Explore, learn, and contribute</h2>
        <div class="feature-grid">
            <article class="block"><h3>Explore disaster history</h3><p>Search and filter published stories by title, date, location, hazard, province, district, and other available details. Browse the timeline and map to see events in context. Story pages include links for sharing, email, copying, and printing or saving as PDF.</p><a class="page-link" href="<?= BASE_URL ?>/#explore">Explore the archive →</a></article>
            <article class="block"><h3>Read human stories</h3><p>Explore published first-person experiences alongside the historical record, or submit an account for editorial review before it is considered for publication.</p><a class="page-link" href="<?= BASE_URL ?>/human-stories">Visit Human Stories →</a></article>
            <article class="block"><h3>Find preparedness guidance</h3><p>Read practical safety guidance and preparedness information, including English and Nepali content, to help individuals and communities prepare for hazards.</p><a class="page-link" href="<?= BASE_URL ?>/preparedness">Read preparedness guides →</a></article>
            <article class="block"><h3>Locate emergency information</h3><p>Use the emergency resource directory to find listed contacts and services. For immediate danger, contact the appropriate local emergency service directly.</p><a class="page-link" href="<?= BASE_URL ?>/resources">Open emergency resources →</a></article>
            <article class="block"><h3>Watch and listen</h3><p>Browse documentaries, interviews, news reports, and other listed media related to disasters and their effects.</p><a class="page-link" href="<?= BASE_URL ?>/documentaries">Browse documentaries →</a></article>
            <article class="block"><h3>Participate and improve the record</h3><p>Share a lived experience, register volunteer skills, or report a correction on a story. Contributions are reviewed and are not a substitute for emergency response.</p><a class="page-link" href="<?= BASE_URL ?>/volunteer">Visit the Volunteer Network →</a></article>
        </div>
    </section>

    <section class="coverage" aria-labelledby="coverage-heading">
        <div class="eyebrow">A broad historical record</div>
        <h2 class="section-heading" id="coverage-heading">Hazards covered</h2>
        <p style="margin:0">The archive is intended to cover a range of natural hazards recorded across Nepal. Coverage and available detail vary by event and source.</p>
        <div class="category-list"><?php foreach ($categories as $category): ?><span><?= e($category) ?></span><?php endforeach; ?></div>
    </section>

    <section aria-labelledby="standards-heading">
        <div class="eyebrow">How information is handled</div>
        <h2 class="section-heading" id="standards-heading">Sources, review, and limitations</h2>
        <div class="detail-grid">
            <article class="block">
                <h3>Sources and editorial approach</h3>
                <p>Records may draw on Government of Nepal and NDRRMA reports, the Department of Hydrology and Meteorology, ICIMOD, USGS, UNESCO, scientific research, and other cited authorities. Story pages include available references. Editors can create and revise records, manage categories, and publish reviewed material through the editorial workspace.</p>
            </article>
            <article class="block">
                <h3>Historical uncertainty</h3>
                <p>Dates, casualty counts, magnitudes, and damage estimates can differ between sources. Older records may be incomplete or use different administrative boundaries and reporting methods. Estimates should be understood in light of their cited source and limitations.</p>
            </article>
            <article class="block">
                <h3>Community contributions</h3>
                <p>Submitted human stories are reviewed before publication. Correction reports help the editors investigate possible errors. Sending a story or report does not guarantee publication or an individual response.</p>
            </article>
            <article class="block">
                <h3>Not an emergency service</h3>
                <p>This archive is for research, education, historical understanding, and preparedness. It does not provide real-time warnings, dispatch responders, or replace official instructions and emergency services.</p>
            </article>
        </div>
    </section>

    <section aria-labelledby="project-credit-heading">
        <div class="eyebrow">Project credit</div>
        <h2 class="section-heading" id="project-credit-heading">Who is behind the project?</h2>
        <div class="ownership">
            <article class="block">
                <h3>AcademiX Digital initiative</h3>
                <p>The site credits the Nepal Disaster Archive as an <strong>AcademiX Digital initiative</strong>. The public project information available here does not identify a legal owner, founder, or individual team biographies, so this page does not make additional ownership or personal claims.</p>
                <p style="margin-top:14px">The archive is presented as a public information and learning resource. Its editorial content and contributions should be evaluated with the sources and limitations noted on each record.</p>
            </article>
            <article class="block">
                <h3>Feedback and participation</h3>
                <p>To suggest a correction, open a published story and use its correction form. To contribute a lived experience or register volunteer skills, use the corresponding public forms. Emergency requests should be directed to local emergency services.</p>
                <a class="page-link" href="<?= BASE_URL ?>/#stories">Find a story and report a correction →</a>
            </article>
        </div>
    </section>

    <p class="last-updated">Archive content changes as stories are reviewed and updated. Last story update: <?= e($lastUpdated ? date('F j, Y', strtotime((string)$lastUpdated)) : 'Not yet available') ?>.</p>
    <a class="page-link" href="<?= BASE_URL ?>/">← Back to the archive</a>
</main>
<footer class="footer">
    <div class="footer-inner">
        <strong>Nepal Disaster Archive</strong>
        <p>Disaster history · Human stories · Preparedness<br>Documenting Disasters. Building Resilience — an AcademiX Digital initiative.</p>
        <div class="social-links" aria-label="Social media links">
            <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3c-3.3 0-5 1.8-5 5v3H6v4h3v8h4v-8h3.2l.8-4H13V9c0-.7.3-1 1-1z"/></svg></a>
            <a href="https://www.instagram.com/academix_digital/" target="_blank" rel="noopener noreferrer" aria-label="AcademiX Digital on Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/></svg></a>
        </div>
    </div>
</footer>
</body>
</html>
