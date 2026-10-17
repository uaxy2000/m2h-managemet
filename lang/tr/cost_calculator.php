<?php

return [
    'title'       => 'Maliyet Hesaplayıcı',
    'language'    => 'Teklif dili',

    'client_name'      => 'Müşteri adı (opsiyonel)',
    'family_members'   => 'Aile üyeleri',
    'total_applicants' => 'Tüm başvuru sahipleri',

    'options'          => 'Seçenekler',
    'minimum'          => 'Minimum :amount',
    'below_minimum'    => 'Program minimumunun altında — hesaplamada :amount kullanılır.',

    'view'          => 'Görünüm',
    'side_by_side'  => 'Yan yana',
    'separate'      => 'Ayrı tablolar',

    'first_year'    => 'İlk yıl maliyeti',
    'five_year'     => ':years yıllık toplam maliyet',
    'costs_in_eur'  => 'Tüm maliyetler Euro cinsindendir',
    'amount'        => 'Tutar',
    'no_option'     => 'Maliyetleri görmek için en az bir seçenek işaretleyin.',

    'members' => [
        'main'           => 'Ana başvuru sahibi',
        'spouse'         => 'Eş',
        'child_under_18' => '18 yaş altı çocuklar',
        'child_over_18'  => '18 yaş üstü çocuklar',
        'parent'         => 'Ebeveynler / büyükanne ve büyükbabalar',
    ],

    'later_member' => 'Aile üyesi',

    'pdf' => [
        'title'        => 'Maliyet Teklifi',
        'prepared_for' => 'Hazırlanan kişi',
        'prepared_by'  => 'Hazırlayan',
        'date'         => 'Tarih',
        'family'       => 'Aile kompozisyonu',
        'count'        => 'Kişi',
        'selected'     => 'Seçilen seçenekler',
        'summary'      => 'Maliyet özeti',
        'option'       => 'Seçenek',
        'comparison'   => 'Maliyet karşılaştırması',
        'details'      => 'Aile üyelerine göre maliyetler',
        'footer'       => 'Bu hesaplama bilgilendirme amaçlıdır ve bağlayıcı bir teklif değildir. Tutarlar program kurallarına göre değişebilir.',
    ],

    'timeline' => [
        'title'        => 'Ödeme takvimi',
        'option'       => 'Takvim seçeneği',
        'estimated'    => 'Oturum kartlarına kadar tahmini süre: ≈ :a–:b ay',
        'step_total'   => 'Adım toplamı',
        'paid_so_far'  => 'Şu ana kadar ödenen',
        'no_payment'   => 'Bu adımda ödeme yok',
        'cumulative'   => 'Geçen süre',
        'week_one'     => '1. hafta',
        'month_single' => '≈ :n. ay',
        'month_range'  => '≈ :a–:b. ay',
        'check'        => 'Takvimdeki ödemelerin toplamı :years yıllık toplam maliyete eşittir.',
    ],

    'programs' => [
        'malta_mprp' => [
            'name'        => 'Malta Daimi Oturum Programı (MPRP)',
            'country'     => 'Malta',
            'description' => 'Gayrimenkul kiralama veya satın alma ve devlet katkı payı ile daimi oturum.',
            'family_hint' => 'Yabancılar yakın akrabalarını ekleyebilir: eş, çocuklar, ebeveynler ve büyükanne ve büyükbabalar.',

            'options' => [
                'rent' => [
                    'title'       => 'Gayrimenkul Kiralama Seçeneği',
                    'input'       => 'Yıllık kira',
                    'requirement' => ':years yıl boyunca yıllık en az :amount kira gerekir.',
                ],
                'purchase' => [
                    'title'       => 'Gayrimenkul Satın Alma Seçeneği',
                    'input'       => 'Gayrimenkul bedeli',
                    'requirement' => 'Gayrimenkul satın alımı için minimum tutar: :amount.',
                ],
            ],

            'optional_items' => [
                'temp_card' => 'Geçici oturum kartını dahil et',
            ],

            'asset_requirement' => 'Toplam 500.000 € değerinde varlığın teyit edilmesi gerekir ve bunun 150.000 €\'su likit olmalıdır; veya toplam 650.000 € değerinde varlığın teyit edilmesi gerekir ve bunun 75.000 €\'su likit olmalıdır.',

            'items' => [
                'rent'                => 'Gayrimenkul kirası (:years yıl × :amount)',
                'investment'          => 'Gayrimenkul satın alımı',
                'admin_fee'           => 'İade edilmeyen idari ücret',
                'gov_contribution'    => 'İade edilmeyen devlet katkı payı',
                'ngo_contribution'    => 'Malta\'daki bir sivil toplum kuruluşuna katkı',
                'health_insurance'    => 'Sağlık sigortası',
                'residence_card'      => 'Oturum kartı ücreti (5 yıllık kart)',
                'temp_card'           => 'Geçici oturum kartı ücreti (opsiyonel)',
                'document_processing' => 'Belge işleme ücretleri',
                'service_fee'         => 'Hizmet bedeli',
                'acf'                 => 'Yıllık uyum ücreti — ACF (:years yıl × :amount)',
            ],

            'notes' => [
                1 => 'İade edilmeyen idari ücret iki taksitte ödenir: başvurunun sunulmasından itibaren 1 ay içinde 15.000 € ve İlke Onay Mektubu\'nun alınmasından itibaren 2 ay içinde 45.000 €. Ayrıca bakmakla yükümlü olunan her kişi için 7.500 € iade edilmeyen idari ücret alınır ve onaydan itibaren 2 ay içinde ödenir.',
                2 => 'İade edilmeyen devlet katkı payı tek seferde ödenir, taksitlendirilemez ve İlke Onay Mektubu\'nun düzenlenmesinden itibaren 8 ay içinde ödenmelidir.',
                3 => 'Belge işleme ücretleri yalnızca referans amaçlıdır ve gerektiğinde belgelerin tercüme ve tasdik işlemlerini kapsar. Tutar değişiklik gösterebilir ve genellikle belge sayısına bağlıdır.',
                4 => 'Hizmet bedeli aile üyesi sayısına göre değişiklik gösterebilir.',
                5 => 'İlk 5 yıl boyunca her yıl yıllık uyum raporu sunulmalıdır (güncel olarak 975 €, değişiklik gösterebilir).',
            ],

            'timeline' => [
                'steps' => [
                    'due_diligence' => [
                        'title'       => 'Ön inceleme ve sözleşme',
                        'duration'    => '2 gün',
                        'description' => 'Gizlilik içinde yapılan ön inceleme, başvuru sahibinin programa katılmasına engel bir durum olmadığını teyit eder. Ardından hizmet sözleşmesi imzalanır.',
                    ],
                    'temp_permit' => [
                        'title'       => 'Geçici oturum izni (opsiyonel)',
                        'duration'    => '1+ ay',
                        'description' => 'Tam başvurudan önce ana başvuru sahibi ve bağımlıları 1 yıllık geçici oturum izni alabilir. Biyometri ve belgeler için Malta\'ya gidilir.',
                        'extras'      => ['Biyometri için Malta seyahati (dahil değil)'],
                    ],
                    'documents' => [
                        'title'       => 'Belgelerin hazırlanması',
                        'duration'    => '3+ hafta',
                        'description' => 'Avukatlarımız belgelerin toplanması, tasdiki ve tercümesi sürecini yönetir; bu işlemler gerekli standartlar için genellikle Malta\'da yapılır.',
                    ],
                    'application' => [
                        'title'       => 'Başvuru ve inceleme',
                        'duration'    => 'Genellikle 3–6 ay',
                        'description' => 'Residency Malta Agency başvuruyu 90–180 günde inceler ve İlke Onay Mektubu düzenler. Geçici oturum alındıysa bu adım ondan sonraki 6 ay içinde tamamlanmalıdır.',
                    ],
                    'investment' => [
                        'title'       => 'Yatırım şartlarının yerine getirilmesi',
                        'duration'    => 'Onaydan sonra 8 ay içinde',
                        'description' => 'Onaydan sonra: idari ücretin kalanı (2 ay içinde) ödenir, gayrimenkul kiralanır veya satın alınır, devlet katkı payı (8 ay içinde) ödenir ve bir STK\'ya bağış yapılır. Ardından daimi oturum sertifikası düzenlenir.',
                    ],
                    'biometrics' => [
                        'title'       => 'Biyometri ve oturum kartları',
                        'duration'    => '4+ hafta',
                        'description' => 'Tüm başvuru sahipleri biyometriyi Malta\'da şahsen verir (2 yaşına kadar bebekler muaf). Kartlar yaklaşık 4 haftada basılır.',
                        'extras'      => ['Biyometri için Malta seyahati (dahil değil)'],
                    ],
                    'after' => [
                        'title'       => 'Daimi oturum sonrası',
                        'duration'    => '1.–:years. yıllar',
                        'description' => 'Oturum statüsünün korunması için her yıl yapılan ödemeler.',
                    ],
                ],
                'payments' => [
                    'service_fee_1'       => 'Hizmet bedeli — 1. ödeme (%50)',
                    'service_fee_2'       => 'Hizmet bedeli — 2. ödeme (%25)',
                    'service_fee_3'       => 'Hizmet bedeli — son ödeme (%25)',
                    'admin_initial'       => 'İdari ücret — ilk kısım',
                    'admin_final'         => 'İdari ücret — son kısım (bağımlılar dahil)',
                    'rent_first'          => 'Gayrimenkul kirası — 1. yıl',
                    'rent_remaining'      => 'Gayrimenkul kirası — 2.–:years. yıllar',
                    'investment'          => 'Gayrimenkul satın alımı',
                    'health_insurance'    => 'Sağlık sigortası',
                    'temp_card'           => 'Geçici oturum kartı ücreti',
                    'document_processing' => 'Belge işleme (tercüme ve tasdik)',
                    'gov_contribution'    => 'Devlet katkı payı',
                    'ngo_contribution'    => 'Malta\'daki bir STK\'ya bağış',
                    'residence_card'      => '5 yıllık oturum kartı ücreti',
                    'acf'                 => 'Yıllık uyum ücreti (ACF) — :years yıl',
                ],
                'disclaimer' => 'Aşamalar, süreler ve ücretler genel bilgi amaçlıdır ve düzenli olarak güncellenir. Koşullar mevzuata veya kişisel durumlara göre değişebilir.',
            ],

            'later_title'   => 'Süresiz oturum izni alındıktan sonra bir aile üyesinin eklenmesi',
            'later_intro'   => 'Malta\'da kalıcı oturum izni aldıktan sonra ana başvuru sahibi aşağıdaki aile üyelerini dahil edebilir:',
            'later_members' => [
                'spouse'               => 'Eş',
                'child_under_18'       => '18 yaş altı çocuklar',
                'child_over_18'        => '18 yaş üstü çocuklar',
                'disabled_adult_child' => 'Ana başvuru sahibinin veya eşinin engelli olduğu belgelenmiş yetişkin çocuğu',
                'parent'               => 'Ebeveynler / büyükanne ve büyükbabalar',
                'child_spouse'         => 'Gelin / damat',
                'grandchild'           => 'Torunlar',
            ],
            'later_rules' => [
                'Eşi;',
                '18 yaşından küçük çocukları (eşinin çocukları dahil), ana başvuru sahibine esas olarak bağımlı olmaları şartıyla;',
                '18 yaş ve üzerindeki çocukları (eşinin çocukları dahil), ana başvuru sahibine esas olarak bağımlı olmaları şartıyla;',
                'Ana başvuru sahibinin veya eşinin engelli olduğu belgelenmiş yetişkin çocuğu;',
                'Ebeveynleri veya büyükanne ve büyükbabaları (eşinin ebeveynleri ve büyükanne ve büyükbabaları dahil);',
                'Ana başvuru sahibine bağımlı olmayan çocuklarının eşleri;',
                'Ana başvuru sahibine bağımlı olmayan torunları.',
            ],
        ],
    ],
];
