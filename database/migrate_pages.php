<?php

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}",
    $db['username'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connecting to MySQL sandouk_db...\n";

// Check if columns exist
$cols = $pdo->query("SHOW COLUMNS FROM content_pages")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('title_en', $cols)) {
    echo "Adding title_en column...\n";
    $pdo->exec("ALTER TABLE content_pages ADD COLUMN title_en VARCHAR(200) NULL AFTER title");
}

if (!in_array('content_en', $cols)) {
    echo "Adding content_en column...\n";
    $pdo->exec("ALTER TABLE content_pages ADD COLUMN content_en LONGTEXT NULL AFTER content");
}

if (!in_array('sections_json', $cols)) {
    echo "Adding sections_json column...\n";
    $pdo->exec("ALTER TABLE content_pages ADD COLUMN sections_json LONGTEXT NULL AFTER content_en");
}

echo "Table content_pages schema verified!\n";

// Seed rich content for about, terms, privacy, contact, loan_commitment
$pagesData = [
    'about' => [
        'title' => 'من نحن',
        'title_en' => 'About Us',
        'content' => "تأسس صندوق العائلة انطلاقاً من مبدأ التعاون والتكافل الاجتماعي، ليكون مظلة مالية آمنة ومنصة ادخارية تكافلية تخدم جميع أفراد العائلة.\n\nيسعى الصندوق إلى تعزيز أواصر القربى، وتوفير الدعم المالي الميسر الخالي من أي فوائد ربوية، وتمكين الأعضاء من تنمية مدخراتهم من خلال نظام أسهم مرن وموثق بكل دقة وشفافية.",
        'content_en' => "The Family Solidarity Fund was founded on the principles of mutual support and solidarity, serving as a trusted financial haven and savings ecosystem for all family members.\n\nThe fund aims to strengthen kinship bonds, provide easy interest-free financial assistance, and empower members to grow their personal savings through a flexible and transparent shareholding system.",
        'sections' => [
            [
                'icon' => 'shield-check',
                'badge' => 'حوكمة وأمان',
                'badge_en' => 'Governance',
                'title_ar' => 'الأمان والشفافية التامة',
                'title_en' => 'Security & Full Transparency',
                'body_ar' => 'إدارة محاسبية مدققة ودفتر أستاذ إلكتروني فوري يوضح إجمالي رصيد الصندوق، الاشتراكات، وحركة القروض بكل وضوح.',
                'body_en' => 'Audited accounting management and real-time electronic ledger showing total fund assets, subscriptions, and loan movements with complete clarity.'
            ],
            [
                'icon' => 'handshake',
                'badge' => 'قرض حسن',
                'badge_en' => 'Interest-Free',
                'title_ar' => 'تمويل ميسر بدون فوائد',
                'title_en' => 'Zero-Interest Family Loans',
                'body_ar' => 'قروض تكافلية متوافقة 100% مع الضوابط الشرعية، تمنح وفقاً لنظام نقاط وجدولة واضحة لحفظ حقوق الجميع.',
                'body_en' => 'Solidarity loans fully compliant with Islamic principles, granted through a structured queue and scoring system to guarantee fairness.'
            ],
            [
                'icon' => 'award',
                'badge' => 'استدامة',
                'badge_en' => 'Sustainability',
                'title_ar' => 'حفظ حقوق المساهمين والورثة',
                'title_en' => 'Shareholder & Heir Protection',
                'body_ar' => 'قيمة الأسهم محفوظة ومسجلة رسمياً بالاسم والهوية، مع إمكانية استردادها أو توريثها وفق اللائحة المعتمدة.',
                'body_en' => 'Share values are officially documented under members verified IDs, fully redeemable or inheritable per bylaws.'
            ],
            [
                'icon' => 'smartphone',
                'badge' => 'بوابة رقمية',
                'badge_en' => 'Digital Portal',
                'title_ar' => 'تجربة رقمية ذكية وشاملة',
                'title_en' => 'Smart & Seamless Digital Experience',
                'body_ar' => 'إمكانية متابعة حسابك، حاسبة القروض التفاعلية، وإشعارات السداد عبر الهاتف أو الحاسوب على مدار الساعة.',
                'body_en' => 'Access account balance, simulate loan repayments, and receive reminders via mobile or desktop 24/7.'
            ]
        ]
    ],
    'terms' => [
        'title' => 'شروط وأحكام الصندوق',
        'title_en' => 'Terms & Fund Regulations',
        'content' => "تهدف هذه الشروط واللوائح إلى تنظيم عمل الصندوق التكافلي، وضمان العدالة وتكافؤ الفرص بين جميع المشتركين.\n\nإن انضمام أي عضو للصندوق أو تقديمه لأي طلب تمويل يعتبر موافقة كاملة وصريحة على كافة المواد والبنود المنصوص عليها في هذه اللائحة.",
        'content_en' => "These regulations govern the operations of the Family Solidarity Fund, ensuring fairness, equity, and transparency among all active members.\n\nBy registering or submitting any financing request, the member fully acknowledges and agrees to comply with all terms and bylaws stated herein.",
        'sections' => [
            [
                'icon' => 'check-circle',
                'badge' => 'المادة 1',
                'badge_en' => 'Article 1',
                'title_ar' => 'شروط الانضمام والعضوية',
                'title_en' => 'Membership Eligibility',
                'body_ar' => 'العضوية متاحة لجميع أفراد العائلة المستوفين لسن الأهلية، مع الالتزام بالاكتتاب بحد أدنى سهم واحد وسداد رسم التأسيس.',
                'body_en' => 'Membership is open to qualified adult family members, with a commitment to subscribe to at least one share and pay the founding fee.'
            ],
            [
                'icon' => 'calendar-check',
                'badge' => 'المادة 2',
                'badge_en' => 'Article 2',
                'title_ar' => 'الاشتراك الشهري ومواعيد السداد',
                'title_en' => 'Monthly Contributions & Due Dates',
                'body_ar' => 'يستحق قسط السهم الشهري في موعد أقصاه اليوم العاشر من كل شهر ميلادي لضمان استمرارية سيولة الصندوق.',
                'body_en' => 'Monthly share installments are due no later than the 10th day of each calendar month to sustain fund liquidity.'
            ],
            [
                'icon' => 'cash-coin',
                'badge' => 'المادة 3',
                'badge_en' => 'Article 3',
                'title_ar' => 'ضوابط صرف القروض والتخصيص',
                'title_en' => 'Loan Granting & Repayment Rules',
                'body_ar' => 'يتم الصرف وفق الأولويات ونظام الدور الآلي، بحد أقصى يعتمد على عدد أسهم العضو وسجله الائتماني في الالتزام.',
                'body_en' => 'Disbursements follow automated priority queues, capped by the members active shares and historical commitment score.'
            ],
            [
                'icon' => 'shield-exclamation',
                'badge' => 'المادة 4',
                'badge_en' => 'Article 4',
                'title_ar' => 'حالات التأخر والتعثر',
                'title_en' => 'Default & Arrears Policy',
                'body_ar' => 'في حال تعثر العضو يتم مراجعة حالته من قبل لجنة الصندوق لإيجاد جدولة ميسرة تناسب ظروفه دون الإضرار بالصندوق.',
                'body_en' => 'In cases of hardship, the committee reviews the case to structure flexible grace periods without risking capital.'
            ]
        ]
    ],
    'privacy' => [
        'title' => 'سياسة الخصوصية وسرية البيانات',
        'title_en' => 'Privacy & Data Protection',
        'content' => "نحن نولي خصوصية بيانات أفراد العائلة وسجلاتهم المالية أقصى درجات الاهتمام والسرية.\n\nتوضح هذه الوثيقة كيفية جمع البيانات واستخدامها وحمايتها داخل المنظومة الإلكترونية.",
        'content_en' => "We handle our family members personal and financial records with utmost confidentiality and state-of-the-art security.\n\nThis policy explains how data is collected, securely processed, and protected within our digital ecosystem.",
        'sections' => [
            [
                'icon' => 'lock',
                'badge' => 'تشفير',
                'badge_en' => 'Encryption',
                'title_ar' => 'تشفير السجلات وحمايتها',
                'title_en' => 'Data Encryption & Safe Storage',
                'body_ar' => 'جميع كلمات المرور والبيانات البنكية الحساسة مشفرة بأحدث خوارزميات التشفير القياسية العالمية.',
                'body_en' => 'All passwords and sensitive transaction records are encrypted using modern industry-standard security protocols.'
            ],
            [
                'icon' => 'eye-slash',
                'badge' => 'سرية',
                'badge_en' => 'Confidentiality',
                'title_ar' => 'حظر مشاركة البيانات مع أي طرف خارجي',
                'title_en' => 'Strict Non-Disclosure Guarantee',
                'body_ar' => 'لا يتم مشاركة أي معلومة عن المشتركين أو أرصدتهم أو معاملاتهم مع أي جهة تجارية أو خارجية نهائياً.',
                'body_en' => 'No personal, financial, or contact records are ever shared or monetized with any third party under any circumstances.'
            ],
            [
                'icon' => 'person-badge',
                'badge' => 'صلاحيات',
                'badge_en' => 'Access Control',
                'title_ar' => 'صلاحيات وصول دقيقة ومحددة',
                'title_en' => 'Role-Based Access Control',
                'body_ar' => 'لا يطلع على التفاصيل الشخصية إلا إدارة الصندوق المخولة والمعتمدة لحفظ الخصوصية التامة.',
                'body_en' => 'Only authorized administrative committee members can review financial records necessary for operational decisions.'
            ]
        ]
    ],
    'contact' => [
        'title' => 'تواصل مع إدارة الصندوق',
        'title_en' => 'Contact Administration',
        'content' => "يسعدنا دائماً استقبال استفساراتكم، مقترحاتكم، أو طلباتكم الخاصة.\n\nيمكنكم التواصل المباشر مع لجنة الصندوق عبر القنوات المعتمدة أدناه.",
        'content_en' => "We welcome all inquiries, suggestions, and assistance requests from our dear family members.\n\nReach out directly to the executive committee through the official channels listed below.",
        'sections' => [
            [
                'icon' => 'telephone',
                'badge' => 'هاتف',
                'badge_en' => 'Phone',
                'title_ar' => 'الخط الساخن والمكالمات',
                'title_en' => 'Official Telephone Line',
                'body_ar' => 'متاح يومياً من 4 عصراً حتى 9 مساءً للرد على كافة الاستفسارات الطارئة والتنظيمية.',
                'body_en' => 'Available daily from 4:00 PM to 9:00 PM for urgent queries and executive assistance.'
            ],
            [
                'icon' => 'envelope',
                'badge' => 'إيميل',
                'badge_en' => 'Email',
                'title_ar' => 'البريد الإلكتروني الرسمي',
                'title_en' => 'Official Support Email',
                'body_ar' => 'info@sandouk.local - يتم الرد على كافة الرسائل الرسمية خلال 24 ساعة عمل.',
                'body_en' => 'info@sandouk.local - All formal communications are addressed within 24 working hours.'
            ],
            [
                'icon' => 'chat-dots',
                'badge' => 'تذاكر',
                'badge_en' => 'Helpdesk',
                'title_ar' => 'نظام الرسائل الداخلي',
                'title_en' => 'Internal Member Helpdesk',
                'body_ar' => 'يمكن للأعضاء المسجلين إرسال تذاكر دعم فني ومتابعة حالتها مباشرة من لوحة تحكم العضو.',
                'body_en' => 'Registered members can submit tickets and track inquiries directly from their personal dashboard.'
            ]
        ]
    ]
];

$stmt = $pdo->prepare("UPDATE content_pages SET title = :title, title_en = :title_en, content = :content, content_en = :content_en, sections_json = :sections_json WHERE slug = :slug");
$insertStmt = $pdo->prepare("INSERT INTO content_pages (slug, title, title_en, content, content_en, sections_json) VALUES (:slug, :title, :title_en, :content, :content_en, :sections_json)");

foreach ($pagesData as $slug => $data) {
    $sectionsJson = json_encode($data['sections'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    // Check if slug exists
    $check = $pdo->prepare("SELECT id FROM content_pages WHERE slug = ?");
    $check->execute([$slug]);
    if ($check->fetch()) {
        $stmt->execute([
            ':slug' => $slug,
            ':title' => $data['title'],
            ':title_en' => $data['title_en'],
            ':content' => $data['content'],
            ':content_en' => $data['content_en'],
            ':sections_json' => $sectionsJson,
        ]);
        echo "Updated content page: {$slug}\n";
    } else {
        $insertStmt->execute([
            ':slug' => $slug,
            ':title' => $data['title'],
            ':title_en' => $data['title_en'],
            ':content' => $data['content'],
            ':content_en' => $data['content_en'],
            ':sections_json' => $sectionsJson,
        ]);
        echo "Created content page: {$slug}\n";
    }
}

echo "All pages successfully migrated and seeded!\n";
