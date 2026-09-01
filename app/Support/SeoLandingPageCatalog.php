<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Provides reviewed, privacy-safe content for public SEO landing pages.
 *
 * Member records and database-derived counts must never be added here. These
 * pages describe the service and regional matrimonial considerations only.
 */
final class SeoLandingPageCatalog
{
    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $pageKey): ?array
    {
        return match ($pageKey) {
            'sikh-matrimony' => self::sikhMatrimony(),
            'how-it-works' => self::howItWorks(),
            'verification-and-safety' => self::verificationAndSafety(),
            'faq' => self::faq(),
            'delhi' => self::delhi(),
            'punjab' => self::punjab(),
            'chandigarh' => self::chandigarh(),
            'canada' => self::canada(),
            'toronto' => self::toronto(),
            'vancouver' => self::vancouver(),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private static function sikhMatrimony(): array
    {
        return self::page(
            routeName: 'web.seo.sikh-matrimony',
            title: 'Sikh Matrimony for Meaningful Matches | SikhanandKaraj',
            description: 'Discover a privacy-focused Sikh matrimony platform for '
                . 'individuals and families seeking compatible life partners '
                . 'rooted in Sikh values.',
            eyebrow: 'Sikh matrimony with trust and purpose',
            heading: 'A thoughtful approach to Sikh matrimony',
            introduction: 'SikhanandKaraj helps Sikh individuals and families begin a '
                . 'marriage-focused search with clear profiles, compatible '
                . 'preferences and controlled access to personal information.',
            sections: [
                self::section(
                    'Matrimony rooted in shared values',
                    [
                        'Finding a life partner is more than comparing a few '
                            . 'profile fields. Families may consider faith, outlook, '
                            . 'education, profession, location, family expectations and '
                            . 'plans for the future. SikhanandKaraj provides a structured '
                            . 'place to present those details without turning the process '
                            . 'into casual social networking.',
                        'The platform is intended exclusively for matrimonial '
                            . 'purposes. Members can describe themselves, set partner '
                            . 'preferences and explore relevant connections while Sikh '
                            . 'traditions and the role of families remain respected.',
                    ]
                ),
                self::section(
                    'Designed around privacy-conscious introductions',
                    [
                        'Matrimonial profiles can contain sensitive personal and '
                            . 'family information. SikhanandKaraj therefore keeps member '
                            . 'profiles away from public search engines and applies '
                            . 'account, membership, gender and interest rules before '
                            . 'detailed profiles are shown.',
                    ],
                    [
                        'Private member profiles are not published as SEO pages.',
                        'Profile photographs use controlled delivery and visibility rules.',
                        'Contact and verified information is displayed only through '
                            . 'authorised application flows.',
                        'Members can report or block suspicious or unwanted interactions.',
                    ]
                ),
                self::section(
                    'A clearer path from profile to conversation',
                    [
                        'A complete and accurate profile helps another member '
                            . 'understand essential compatibility before either family '
                            . 'invests time in a conversation. Preference-based matching, '
                            . 'interests and profile review tools help members move '
                            . 'through the process in a deliberate sequence.',
                        'No verification badge or matching score can replace '
                            . 'personal judgement. Members and families should '
                            . 'communicate gradually, confirm important information '
                            . 'independently and involve trusted family members when '
                            . 'appropriate.',
                    ]
                ),
            ],
            faqs: [
                self::faqItem(
                    'Is SikhanandKaraj a Sikh dating website?',
                    'No. SikhanandKaraj is intended only for Sikh matrimonial '
                        . 'purposes and meaningful marriage-focused introductions.'
                ),
                self::faqItem(
                    'Are member profiles visible on Google?',
                    'No. Public search pages contain general service '
                        . 'information only. Member profiles remain within protected '
                        . 'application routes and are excluded from the sitemap.'
                ),
                self::faqItem(
                    'Can families participate in the search?',
                    'Yes. A parent or authorised family member may assist with '
                        . 'a profile when the member has consented and all submitted '
                        . 'information remains accurate.'
                ),
            ],
            breadcrumbs: self::breadcrumbs('Sikh Matrimony', 'web.seo.sikh-matrimony'),
            relatedLinks: [
                self::link('How SikhanandKaraj Works', 'web.seo.how-it-works'),
                self::link('Verification and Safety', 'web.seo.verification-safety'),
                self::link('Membership Plans', 'web.information.membership-plans'),
                self::link('Sikh Matrimony in Canada', 'web.seo.location.canada'),
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function howItWorks(): array
    {
        return self::page(
            routeName: 'web.seo.how-it-works',
            title: 'How SikhanandKaraj Works | Sikh Matrimony',
            description: 'Learn how to register, complete your Sikh matrimonial '
                . 'profile, set preferences, discover matches and communicate '
                . 'safely on SikhanandKaraj.',
            eyebrow: 'A straightforward matrimonial journey',
            heading: 'How SikhanandKaraj works',
            introduction: 'The platform guides members from registration to '
                . 'meaningful introductions through a structured process that '
                . 'keeps profile quality, compatibility and privacy in view.',
            sections: [
                self::section(
                    '1. Register and verify your account',
                    [
                        'Registration begins with an active mobile number. Mobile '
                            . 'verification establishes the member account before '
                            . 'protected profile features become available. Email can be '
                            . 'added and verified separately through Account Settings.',
                        'Use your own contact details and never share an OTP, '
                            . 'password or account access with an unknown person.',
                    ]
                ),
                self::section(
                    '2. Build an accurate matrimonial profile',
                    [
                        'Complete the profile sections covering basic details, '
                            . 'education and profession, family information, lifestyle '
                            . 'and an introduction about yourself. Add current, '
                            . 'appropriate photographs and keep important details '
                            . 'updated.',
                    ],
                    [
                        'Describe education and profession accurately.',
                        'Explain family and lifestyle information clearly.',
                        'Write an original About Me section rather than copying generic text.',
                        'Upload photographs that genuinely represent the member.',
                    ]
                ),
                self::section(
                    '3. Set preferences and explore compatible profiles',
                    [
                        'Partner preferences help the matching process compare '
                            . 'important criteria without treating a percentage as a '
                            . 'final decision. Search and match tools can then surface '
                            . 'profiles according to current application rules and '
                            . 'eligibility.',
                        'When a profile appears relevant, an interest provides a '
                            . 'clear, respectful way to indicate intent. Profile access '
                            . 'and later actions continue to follow membership and '
                            . 'gender-specific privacy rules.',
                    ]
                ),
                self::section(
                    '4. Verify, communicate and decide carefully',
                    [
                        'Verification features can add useful trust signals, but '
                            . 'members should still confirm identity, family details and '
                            . 'intentions independently. Move conversations gradually, '
                            . 'include family when appropriate and meet only in safe '
                            . 'circumstances.',
                    ]
                ),
            ],
            faqs: [],
            breadcrumbs: self::breadcrumbs('How It Works', 'web.seo.how-it-works'),
            relatedLinks: [
                self::link('Sikh Matrimony', 'web.seo.sikh-matrimony'),
                self::link('Verification and Safety', 'web.seo.verification-safety'),
                self::link('Frequently Asked Questions', 'web.seo.faq'),
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function verificationAndSafety(): array
    {
        return self::page(
            routeName: 'web.seo.verification-safety',
            title: 'Sikh Matrimony Verification and Safety | SikhanandKaraj',
            description: 'Understand SikhanandKaraj verification signals, profile '
                . 'privacy and practical safety steps for secure online '
                . 'matrimonial conversations.',
            eyebrow: 'Trust signals with responsible safeguards',
            heading: 'Verification, privacy and matrimonial safety',
            introduction: 'Technology can support safer introductions, but it cannot '
                . 'replace careful judgement. SikhanandKaraj combines account '
                . 'and profile safeguards with practical steps every member '
                . 'and family should follow.',
            sections: [
                self::section(
                    'What a verification status means',
                    [
                        'A verification status confirms only the specific check '
                            . 'represented by that status. Mobile and email verification '
                            . 'confirm control of a contact channel. Aadhaar review and '
                            . 'Live Introduction review follow their own submission and '
                            . 'moderation processes.',
                        'A badge is not a guarantee of character, compatibility, '
                            . 'marital status, finances or future conduct. Always '
                            . 'evaluate the complete situation rather than relying on one '
                            . 'signal.',
                    ]
                ),
                self::section(
                    'How profile privacy is protected',
                    [
                        'Member profiles are not made into public search-engine '
                            . 'landing pages. Protected routes, authorised media services '
                            . 'and visibility rules control how profile details and '
                            . 'photographs are delivered inside the application.',
                    ],
                    [
                        'Do not publish phone numbers, email addresses or home '
                            . 'addresses in open profile text.',
                        'Use platform interest and profile-access flows before '
                            . 'exchanging more information.',
                        'Review photo and video visibility choices carefully.',
                        'Report or block a profile when an interaction becomes '
                            . 'suspicious or unwanted.',
                    ]
                ),
                self::section(
                    'Financial and identity safety',
                    [
                        'Never send money, share an OTP, disclose a password or '
                            . 'reveal a UPI PIN or banking credential to another member. '
                            . 'Be cautious when someone creates urgency, avoids '
                            . 'reasonable verification, asks for secrecy or repeatedly '
                            . 'changes personal details.',
                        'Before making a serious commitment, families should '
                            . 'independently verify identity, marital status, employment, '
                            . 'education and family information through appropriate '
                            . 'lawful means.',
                    ]
                ),
                self::section(
                    'Meeting and reporting safely',
                    [
                        'For an initial meeting, choose a public place, tell a '
                            . 'trusted person where you are going and arrange your own '
                            . 'transportation. If conduct violates platform rules or '
                            . 'appears fraudulent, preserve relevant evidence and use the '
                            . 'available report, grievance or fraud-alert channels.',
                    ]
                ),
            ],
            faqs: [
                self::faqItem(
                    'Does verification guarantee that a member is genuine?',
                    'No. Verification confirms a particular completed check. '
                        . 'Members must still perform their own careful evaluation '
                        . 'and independent verification.'
                ),
                self::faqItem(
                    'Should I send money to another matrimonial member?',
                    'No. Do not send money or disclose banking credentials, '
                        . 'OTPs, passwords or PINs to another member.'
                ),
                self::faqItem(
                    'What should I do about suspicious behaviour?',
                    'Stop sharing information, preserve relevant evidence, '
                        . 'block the profile if necessary and report the matter '
                        . 'through the platform support or grievance process.'
                ),
            ],
            breadcrumbs: self::breadcrumbs(
                'Verification and Safety',
                'web.seo.verification-safety'
            ),
            relatedLinks: [
                self::link('Fraud Alert', 'web.legal.fraud-alert'),
                self::link('Privacy Policy', 'web.legal.privacy'),
                self::link('How It Works', 'web.seo.how-it-works'),
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function faq(): array
    {
        return self::page(
            routeName: 'web.seo.faq',
            title: 'Sikh Matrimony Frequently Asked Questions | SikhanandKaraj',
            description: 'Find answers about SikhanandKaraj registration, profiles, '
                . 'matching, privacy, verification, membership and '
                . 'matrimonial safety.',
            eyebrow: 'Clear answers before you begin',
            heading: 'Frequently asked questions',
            introduction: 'These answers explain the platform at a general level. '
                . 'Account eligibility, profile access and membership '
                . 'capabilities continue to follow the current application '
                . 'rules.',
            sections: [
                self::section(
                    'Using these answers',
                    [
                        'SikhanandKaraj is built for marriage-focused Sikh '
                            . 'introductions. Read the answers below together with the '
                            . 'Terms and Conditions, Privacy Policy, Fraud Alert and '
                            . 'current Membership Plans.',
                    ]
                ),
            ],
            faqs: [
                self::faqItem(
                    'Who can create a profile?',
                    'A person seeking a Sikh matrimonial alliance may register, '
                        . 'or an authorised family member may assist with the '
                        . 'person’s knowledge and consent. Submitted information must '
                        . 'be accurate.'
                ),
                self::faqItem(
                    'Is SikhanandKaraj a dating platform?',
                    'No. The platform is intended exclusively for matrimonial '
                        . 'introductions and should not be used for casual dating or '
                        . 'unrelated social networking.'
                ),
                self::faqItem(
                    'Can Google see member profiles?',
                    'No. Member profiles are protected application content, '
                        . 'excluded from the public sitemap and not used as SEO '
                        . 'landing pages.'
                ),
                self::faqItem(
                    'How are matches selected?',
                    'The platform compares profile and partner-preference '
                        . 'information through its current matching rules. A match '
                        . 'percentage is guidance, not a promise of compatibility.'
                ),
                self::faqItem(
                    'What can verification tell me?',
                    'Verification confirms the specific contact, document or '
                        . 'review step shown by the corresponding status. It does not '
                        . 'guarantee character, finances or compatibility.'
                ),
                self::faqItem(
                    'Can I control who sees my information?',
                    'Profile access, photographs and protected details follow '
                        . 'the application’s current account, membership, interest '
                        . 'and privacy rules. Avoid placing direct contact '
                        . 'information in open profile text.'
                ),
                self::faqItem(
                    'What should I do if someone requests money?',
                    'Do not pay or share financial credentials. Stop the '
                        . 'interaction and report suspicious conduct through the '
                        . 'platform’s report, support or grievance channels.'
                ),
                self::faqItem(
                    'Where can I compare paid plans?',
                    'The public Membership Plans page displays current plan '
                        . 'durations, prices and capabilities from the authoritative '
                        . 'membership-plan data used by the application.'
                ),
            ],
            breadcrumbs: self::breadcrumbs('FAQ', 'web.seo.faq'),
            relatedLinks: [
                self::link('How It Works', 'web.seo.how-it-works'),
                self::link('Membership Plans', 'web.information.membership-plans'),
                self::link('Contact and Grievances', 'web.legal.grievances'),
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function delhi(): array
    {
        return self::locationPage(
            routeName: 'web.seo.location.delhi',
            place: 'Delhi',
            title: 'Sikh Matrimony in Delhi | SikhanandKaraj',
            description: 'Explore a privacy-focused approach to Sikh matrimony in '
                . 'Delhi and Delhi NCR, with guidance for profiles, '
                . 'preferences and family-led introductions.',
            introduction: 'For Sikh individuals and families across Delhi and the '
                . 'wider NCR, matrimonial searches often connect different '
                . 'neighbourhoods, professions, family backgrounds and '
                . 'hometown ties. A clear profile helps those conversations '
                . 'begin with useful context.',
            regionalTitle: 'Matrimonial considerations across Delhi NCR',
            regionalParagraphs: [
                'Delhi’s Sikh community includes families established '
                    . 'across West Delhi, South Delhi, North Delhi, East Delhi '
                    . 'and nearby NCR cities. Daily travel, work location and '
                    . 'long-term residence plans can materially affect how a '
                    . 'match is evaluated.',
                'Some families have close links with Punjab or other parts '
                    . 'of India, while others have relatives overseas. Profiles '
                    . 'should distinguish current residence from family origin '
                    . 'and explain whether relocation is genuinely possible.',
            ],
            regionalItems: [
                'State the present city and work location accurately.',
                'Discuss expectations about living with or near family.',
                'Clarify willingness to relocate within NCR, India or abroad.',
                'Consider commute, career continuity and family responsibilities.',
            ],
            faqs: [
                self::faqItem(
                    'Does this page list public Delhi member profiles?',
                    'No. It provides regional matrimonial guidance only. '
                        . 'Eligible profiles remain within the protected member '
                        . 'application.'
                ),
                self::faqItem(
                    'Can NCR locations be considered in preferences?',
                    'Members should use the available location preferences and '
                        . 'describe any practical NCR or relocation flexibility '
                        . 'accurately in their profile.'
                ),
            ],
            related: [
                self::link('Sikh Matrimony in Punjab', 'web.seo.location.punjab'),
                self::link('Sikh Matrimony in Chandigarh', 'web.seo.location.chandigarh'),
                self::link('Verification and Safety', 'web.seo.verification-safety'),
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function punjab(): array
    {
        return self::locationPage(
            routeName: 'web.seo.location.punjab',
            place: 'Punjab',
            title: 'Sikh Matrimony in Punjab | SikhanandKaraj',
            description: 'Find guidance for Sikh matrimony in Punjab, including '
                . 'family background, district ties, career plans, relocation '
                . 'and privacy-conscious introductions.',
            introduction: 'Punjab remains central to many Sikh families, but '
                . 'matrimonial compatibility cannot be reduced to a district '
                . 'or surname. Education, profession, values, family '
                . 'expectations and future plans deserve equal attention.',
            regionalTitle: 'Looking beyond district and family origin',
            regionalParagraphs: [
                'Families may identify strongly with a village, district or '
                    . 'city even when the member studies or works elsewhere. A '
                    . 'useful matrimonial profile should separate ancestral '
                    . 'origin, permanent family residence and the member’s '
                    . 'current location.',
                'Agricultural, business, professional and '
                    . 'overseas-connected households can have very different '
                    . 'expectations. Clear information about career, residence '
                    . 'and family responsibilities prevents avoidable '
                    . 'misunderstandings.',
            ],
            regionalItems: [
                'Mention current work or study location rather than only the family village.',
                'Explain whether the intended future is in Punjab, another '
                    . 'Indian state or overseas.',
                'Discuss family responsibilities and household expectations respectfully.',
                'Avoid assumptions based solely on district, occupation or overseas relatives.',
            ],
            faqs: [
                self::faqItem(
                    'Are Punjab profiles published on this landing page?',
                    'No. SikhanandKaraj does not expose member profiles or '
                        . 'profile photographs as public SEO content.'
                ),
                self::faqItem(
                    'Should district be the main matching criterion?',
                    'District may matter to some families, but values, '
                        . 'education, profession, lifestyle and realistic future '
                        . 'plans should be considered together.'
                ),
            ],
            related: [
                self::link('Sikh Matrimony in Chandigarh', 'web.seo.location.chandigarh'),
                self::link('Sikh Matrimony in Delhi', 'web.seo.location.delhi'),
                self::link('How It Works', 'web.seo.how-it-works'),
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function chandigarh(): array
    {
        return self::locationPage(
            routeName: 'web.seo.location.chandigarh',
            place: 'Chandigarh',
            title: 'Sikh Matrimony in Chandigarh | SikhanandKaraj',
            description: 'Explore Sikh matrimony guidance for Chandigarh, Mohali and '
                . 'Panchkula with attention to profession, family location, '
                . 'relocation and safe introductions.',
            introduction: 'Chandigarh’s matrimonial search naturally extends across '
                . 'Mohali, Panchkula and surrounding Punjab and Haryana '
                . 'communities. Profiles should reflect this connected '
                . 'regional reality without obscuring the member’s actual '
                . 'residence and plans.',
            regionalTitle: 'A connected Tricity matrimonial search',
            regionalParagraphs: [
                'Many members live in one Tricity area, work in another and '
                    . 'have family roots elsewhere. This makes precise '
                    . 'current-location and professional information more useful '
                    . 'than a broad regional label.',
                'Government service, education, healthcare, technology, '
                    . 'business and professional careers may also shape '
                    . 'relocation decisions. These practical factors should be '
                    . 'discussed early when mutual interest develops.',
            ],
            regionalItems: [
                'Clarify Chandigarh, Mohali or Panchkula residence accurately.',
                'Include present profession and realistic career mobility.',
                'Discuss whether nearby Punjab locations are acceptable.',
                'Account for care responsibilities toward parents and family.',
            ],
            faqs: [
                self::faqItem(
                    'Does Chandigarh include the full Tricity in search?',
                    'The available master locations and member-selected '
                        . 'preferences determine application results. Profiles should '
                        . 'state their exact current city rather than relying on the '
                        . 'general Tricity label.'
                ),
                self::faqItem(
                    'Can members consider nearby Punjab matches?',
                    'Yes, when that aligns with their preferences and practical '
                        . 'plans. Location flexibility should be stated honestly.'
                ),
            ],
            related: [
                self::link('Sikh Matrimony in Punjab', 'web.seo.location.punjab'),
                self::link('Sikh Matrimony in Delhi', 'web.seo.location.delhi'),
                self::link('Membership Plans', 'web.information.membership-plans'),
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function canada(): array
    {
        return self::locationPage(
            routeName: 'web.seo.location.canada',
            place: 'Canada',
            title: 'Sikh Matrimony in Canada | SikhanandKaraj',
            description: 'Explore Sikh matrimony in Canada with practical guidance '
                . 'on province, immigration status, career, relocation, '
                . 'family expectations and cross-border safety.',
            introduction: 'Sikh families in Canada may search locally, across '
                . 'provinces or between Canada and India. Successful '
                . 'introductions require honest discussion of residence, '
                . 'immigration position, profession, family expectations and '
                . 'long-term plans.',
            regionalTitle: 'Clear information for Canadian and cross-border matches',
            regionalParagraphs: [
                'Canada is not one interchangeable location. Distance, time '
                    . 'zones, licensing requirements and provincial job markets '
                    . 'can make Toronto, Vancouver, Calgary, Edmonton and other '
                    . 'communities materially different matrimonial choices.',
                'Immigration status must be described accurately and should '
                    . 'never be used to pressure another person. Marriage '
                    . 'decisions should be based on compatibility and informed '
                    . 'consent, not promises of visas, sponsorship or employment.',
            ],
            regionalItems: [
                'State the city and province where the member actually lives.',
                'Describe immigration or citizenship status truthfully without exaggeration.',
                'Discuss professional licensing and career continuity before relocation.',
                'Consider distance from family, cultural adjustment and financial independence.',
                'Independently verify identity and important cross-border information.',
            ],
            faqs: [
                self::faqItem(
                    'Can Canadian families consider matches in India?',
                    'Yes, if both people genuinely want a cross-border match. '
                        . 'Residence, immigration, career, family and relocation '
                        . 'expectations should be discussed carefully and verified '
                        . 'independently.'
                ),
                self::faqItem(
                    'Does SikhanandKaraj provide immigration advice?',
                    'No. Members should obtain immigration or legal advice only '
                        . 'from appropriately qualified professionals.'
                ),
            ],
            related: [
                self::link('Sikh Matrimony in Toronto', 'web.seo.location.toronto'),
                self::link('Sikh Matrimony in Vancouver', 'web.seo.location.vancouver'),
                self::link('Verification and Safety', 'web.seo.verification-safety'),
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function toronto(): array
    {
        return self::locationPage(
            routeName: 'web.seo.location.toronto',
            place: 'Toronto',
            title: 'Sikh Matrimony in Toronto | SikhanandKaraj',
            description: 'Explore Sikh matrimony guidance for Toronto and the GTA, '
                . 'including location, commute, career, family networks and '
                . 'cross-border considerations.',
            introduction: 'A Toronto matrimonial search often extends across the '
                . 'wider Greater Toronto Area. Exact residence, commute, '
                . 'profession and family networks can be more important than '
                . 'the word Toronto alone.',
            regionalTitle: 'Understanding the Greater Toronto Area',
            regionalParagraphs: [
                'Families may live across Toronto, Brampton, Mississauga, '
                    . 'Scarborough, Etobicoke and other GTA communities. Travel '
                    . 'time and work arrangements can affect whether two '
                    . 'apparently nearby profiles have compatible daily lives.',
                'Members who are new to Canada may also be establishing '
                    . 'careers, completing studies or navigating professional '
                    . 'licensing. Profiles should describe the current situation '
                    . 'honestly instead of presenting uncertain future plans as '
                    . 'settled facts.',
            ],
            regionalItems: [
                'Use the actual GTA city in current-location information.',
                'Discuss work location, commute and remote-work expectations.',
                'Be transparent about study, work permit, permanent '
                    . 'residence or citizenship status.',
                'Clarify whether relocation within Ontario or elsewhere in Canada is realistic.',
            ],
            faqs: [
                self::faqItem(
                    'Does Toronto mean every GTA city?',
                    'No. Toronto and neighbouring GTA cities have distinct '
                        . 'locations and travel considerations. Members should '
                        . 'provide their actual current city.'
                ),
                self::faqItem(
                    'Are Toronto member details visible publicly?',
                    'No. This page contains regional guidance only and does not '
                        . 'publish member profile information.'
                ),
            ],
            related: [
                self::link('Sikh Matrimony in Canada', 'web.seo.location.canada'),
                self::link('Sikh Matrimony in Vancouver', 'web.seo.location.vancouver'),
                self::link('Frequently Asked Questions', 'web.seo.faq'),
            ],
            parentBreadcrumb: self::link(
                'Canada',
                'web.seo.location.canada'
            )
        );
    }

    /** @return array<string, mixed> */
    private static function vancouver(): array
    {
        return self::locationPage(
            routeName: 'web.seo.location.vancouver',
            place: 'Vancouver',
            title: 'Sikh Matrimony in Vancouver | SikhanandKaraj',
            description: 'Explore Sikh matrimony guidance for Vancouver and the '
                . 'Lower Mainland, including exact location, career, family '
                . 'ties and relocation planning.',
            introduction: 'Sikh matrimonial searches around Vancouver frequently '
                . 'include Surrey, Delta, Burnaby, Richmond, Abbotsford and '
                . 'other Lower Mainland communities. Exact location and '
                . 'future plans should be clear from the beginning.',
            regionalTitle: 'Matrimonial planning across the Lower Mainland',
            regionalParagraphs: [
                'Housing, commute, profession and closeness to extended '
                    . 'family can strongly influence long-term plans in British '
                    . 'Columbia. A broad Vancouver label may hide meaningful '
                    . 'differences between communities.',
                'Some families also consider matches elsewhere in Canada, '
                    . 'India or the wider Sikh diaspora. Long-distance '
                    . 'introductions need additional patience, independent '
                    . 'verification and realistic discussion about who could '
                    . 'relocate.',
            ],
            regionalItems: [
                'State the actual Lower Mainland municipality.',
                'Discuss housing and family-living expectations without assumptions.',
                'Explain work location and professional mobility accurately.',
                'Treat interprovincial or international relocation as a joint decision.',
            ],
            faqs: [
                self::faqItem(
                    'Does Vancouver include Surrey and nearby cities?',
                    'The landing page discusses the wider region, but member '
                        . 'profiles should use their actual city so location '
                        . 'preferences remain meaningful.'
                ),
                self::faqItem(
                    'Can Vancouver members consider Toronto or India matches?',
                    'Yes, when both people are open to distance and relocation. '
                        . 'Practical and immigration implications should be discussed '
                        . 'and independently checked.'
                ),
            ],
            related: [
                self::link('Sikh Matrimony in Canada', 'web.seo.location.canada'),
                self::link('Sikh Matrimony in Toronto', 'web.seo.location.toronto'),
                self::link('How It Works', 'web.seo.how-it-works'),
            ],
            parentBreadcrumb: self::link(
                'Canada',
                'web.seo.location.canada'
            )
        );
    }

    /**
     * @param list<array<string, mixed>> $sections
     * @param list<array{question:string, answer:string}> $faqs
     * @param list<array{label:string, routeName:string}> $breadcrumbs
     * @param list<array{label:string, routeName:string}> $relatedLinks
     * @return array<string, mixed>
     */
    private static function page(
        string $routeName,
        string $title,
        string $description,
        string $eyebrow,
        string $heading,
        string $introduction,
        array $sections,
        array $faqs,
        array $breadcrumbs,
        array $relatedLinks
    ): array {
        return compact(
            'routeName',
            'title',
            'description',
            'eyebrow',
            'heading',
            'introduction',
            'sections',
            'faqs',
            'breadcrumbs',
            'relatedLinks'
        );
    }

    /**
     * @param list<string> $regionalParagraphs
     * @param list<string> $regionalItems
     * @param list<array{question:string, answer:string}> $faqs
     * @param list<array{label:string, routeName:string}> $related
     * @param array{label:string, routeName:string}|null $parentBreadcrumb
     * @return array<string, mixed>
     */
    private static function locationPage(
        string $routeName,
        string $place,
        string $title,
        string $description,
        string $introduction,
        string $regionalTitle,
        array $regionalParagraphs,
        array $regionalItems,
        array $faqs,
        array $related,
        ?array $parentBreadcrumb = null
    ): array {
        $breadcrumbs = [
            self::link('Home', 'web.home'),
            self::link('Sikh Matrimony', 'web.seo.sikh-matrimony'),
        ];

        if ($parentBreadcrumb !== null) {
            $breadcrumbs[] = $parentBreadcrumb;
        }

        $breadcrumbs[] = self::link($place, $routeName);

        return self::page(
            routeName: $routeName,
            title: $title,
            description: $description,
            eyebrow: 'Sikh matrimony in ' . $place,
            heading: 'Sikh matrimony in ' . $place,
            introduction: $introduction,
            sections: [
                self::section(
                    $regionalTitle,
                    $regionalParagraphs,
                    $regionalItems
                ),
                self::section(
                    'Create a useful, accurate profile',
                    [
                        'Location pages do not publish member profiles. Inside the '
                            . 'protected application, a complete profile should explain '
                            . 'current residence, education, profession, family context '
                            . 'and genuine relocation preferences so another member can '
                            . 'make an informed initial assessment.',
                        'Avoid adding phone numbers, email addresses, social-media '
                            . 'handles or immigration-document details to open profile '
                            . 'text. Exchange sensitive information only gradually and '
                            . 'through appropriate authorised channels.',
                    ]
                ),
                self::section(
                    'Move from online interest to careful verification',
                    [
                        'When mutual interest develops, both families should '
                            . 'confirm important information independently. Long-distance '
                            . 'and cross-border introductions require particular care '
                            . 'because identity, employment, residence and future plans '
                            . 'may be harder to validate informally.',
                        'Never send money or rely on promises involving visas, '
                            . 'sponsorship, employment or investment. Verification badges '
                            . 'are helpful signals for specific checks, not a substitute '
                            . 'for personal and family due diligence.',
                    ]
                ),
            ],
            faqs: $faqs,
            breadcrumbs: $breadcrumbs,
            relatedLinks: $related
        );
    }

    /**
     * @param list<string> $paragraphs
     * @param list<string> $items
     * @return array{title:string, paragraphs:list<string>, items:list<string>}
     */
    private static function section(
        string $title,
        array $paragraphs,
        array $items = []
    ): array {
        return [
            'title' => $title,
            'paragraphs' => $paragraphs,
            'items' => $items,
        ];
    }

    /** @return array{question:string, answer:string} */
    private static function faqItem(string $question, string $answer): array
    {
        return [
            'question' => $question,
            'answer' => $answer,
        ];
    }

    /** @return array{label:string, routeName:string} */
    private static function link(string $label, string $routeName): array
    {
        return [
            'label' => $label,
            'routeName' => $routeName,
        ];
    }

    /** @return list<array{label:string, routeName:string}> */
    private static function breadcrumbs(
        string $currentLabel,
        string $currentRoute
    ): array {
        return [
            self::link('Home', 'web.home'),
            self::link($currentLabel, $currentRoute),
        ];
    }
}
