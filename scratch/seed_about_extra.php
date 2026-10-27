<?php
require_once __DIR__ . '/../includes/init.php';

$db = db();
$stmt = $db->prepare("SELECT content_json FROM website_content WHERE section_key = 'about'");
$stmt->execute();
$row = $stmt->fetch();
$content = $row ? json_decode($row['content_json'], true) : [];

$content['extra_sections'] = [
    [
        'title' => 'Our State-of-the-Art Software Studio & Labs',
        'badge' => 'Infrastructure & Craftsmanship',
        'alignment' => 'align-left',
        'image_path' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80',
        'content' => "At Zoloverse, we have purpose-built our digital production workspaces and software labs to reflect modern tech industry environments. Equipped with high-performance workstations, real-time collaboration lounges, and continuous integration testing benches, our engineers and apprentices solve challenging engineering problems together every single day.\n\nFrom microservice prototyping to responsive design systems, our space is optimized for creativity, rapid feedback loops, and architectural excellence."
    ],
    [
        'title' => 'Collaborative Innovation & Industry Mentorship',
        'badge' => 'Academy & Culture',
        'alignment' => 'align-right',
        'image_path' => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=1200&q=80',
        'content' => "We believe exceptional software is engineered through rigorous pair programming, code reviews, and transparent technical mentorship. Every student in our academy is mentored by active senior engineers who build enterprise products.\n\nOur philosophy combines theoretical computer science with intense real-world sprint cycles, ensuring developers graduate not just with syntax knowledge, but with battle-tested problem-solving habits and production experience."
    ]
];

$updateStmt = $db->prepare("UPDATE website_content SET content_json = ? WHERE section_key = 'about'");
$updateStmt->execute([json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]);

echo "Updated about content with extra sections successfully!\n";
