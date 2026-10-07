<?php

namespace Database\Seeders;

use App\Enums\ArtCategory;
use App\Enums\MediaType;
use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\ArtistProfile;
use App\Models\Category;
use App\Models\Issue;
use App\Models\MediaGallery;
use App\Models\User;
use Illuminate\Database\Seeder;

class DigitalMagazineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Roles & Users Setup
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@gacta.ly'],
            [
                'name' => 'General Authority Super Admin',
                'password' => bcrypt('password'),
            ]
        );
        $superAdmin->syncRoles(['super-admin']);

        $chiefEditor = User::firstOrCreate(
            ['email' => 'chief@gacta.ly'],
            [
                'name' => 'General Authority Chief Editor',
                'password' => bcrypt('password'),
            ]
        );
        $chiefEditor->syncRoles(['chief-editor']);

        $cinemaEditor = User::firstOrCreate(
            ['email' => 'cinema.editor@gacta.ly'],
            [
                'name' => 'Cinema Section Editor',
                'password' => bcrypt('password'),
            ]
        );
        $cinemaEditor->syncRoles(['section-editor']);

        $theatreEditor = User::firstOrCreate(
            ['email' => 'theatre.editor@gacta.ly'],
            [
                'name' => 'Theatre Section Editor',
                'password' => bcrypt('password'),
            ]
        );
        $theatreEditor->syncRoles(['section-editor']);

        $mediaProducer = User::firstOrCreate(
            ['email' => 'media.producer@gacta.ly'],
            [
                'name' => 'Digital Media Producer',
                'password' => bcrypt('password'),
            ]
        );
        $mediaProducer->syncRoles(['media-producer']);

        // 2. Root & Sub Categories
        $cinema = Category::create([
            'name' => 'Cinema',
            'slug' => 'cinema',
            'description' => 'Libyan and international cinema, screenings, critiques, and film production.',
        ]);

        $theatre = Category::create([
            'name' => 'Theatre',
            'slug' => 'theatre',
            'description' => 'Theatrical performances, stagecraft, dramaturgy, and theater festivals.',
        ]);

        $fineArts = Category::create([
            'name' => 'Fine Arts',
            'slug' => 'fine-arts',
            'description' => 'Visual arts, painting, sculpture, calligraphy, and gallery exhibitions.',
        ]);

        $music = Category::create([
            'name' => 'Music',
            'slug' => 'music',
            'description' => 'Traditional Libyan Ma’luf, folk rhythms, and modern symphonic compositions.',
        ]);

        Category::create([
            'name' => 'Documentary Cinema',
            'slug' => 'documentary-cinema',
            'description' => 'Historical and contemporary Libyan documentary filmmaking.',
            'parent_id' => $cinema->id,
        ]);

        Category::create([
            'name' => 'Stage Directing',
            'slug' => 'stage-directing',
            'description' => 'Contemporary stage direction methodologies in North Africa.',
            'parent_id' => $theatre->id,
        ]);

        // Assign section editors to categories
        $cinemaEditor->managedCategories()->syncWithoutDetaching([$cinema->id]);
        $theatreEditor->managedCategories()->syncWithoutDetaching([$theatre->id]);

        // 3. Magazine Issues
        $issue1 = Issue::create([
            'issue_number' => 1,
            'title' => 'Inaugural Issue: Heritage & Modernity in Libyan Arts',
            'slug' => 'issue-1-heritage-modernity',
            'cover_image' => 'covers/issue-01.jpg',
            'pdf_file_path' => 'issues/issue-01.pdf',
            'published_at' => '2026-01-15',
            'status' => PublicationStatus::Published,
            'pages_count' => 64,
        ]);

        $issue2 = Issue::create([
            'issue_number' => 2,
            'title' => 'Issue #2: The Golden Age of Tripoli National Theatre',
            'slug' => 'issue-2-national-theatre',
            'cover_image' => 'covers/issue-02.jpg',
            'pdf_file_path' => 'issues/issue-02.pdf',
            'published_at' => '2026-04-20',
            'status' => PublicationStatus::Published,
            'pages_count' => 80,
        ]);

        $issue3 = Issue::create([
            'issue_number' => 3,
            'title' => 'Issue #3: Emerging Visions in Independent Libyan Cinema',
            'slug' => 'issue-3-independent-cinema',
            'cover_image' => 'covers/issue-03.jpg',
            'pdf_file_path' => 'issues/issue-03.pdf',
            'published_at' => '2026-11-01',
            'status' => PublicationStatus::Review,
            'pages_count' => 72,
        ]);

        // 4. Artist Profiles
        $artist1 = ArtistProfile::create([
            'full_name' => 'Mohamed Zwawi',
            'slug' => 'mohamed-zwawi',
            'art_category' => ArtCategory::FineArts,
            'biography' => 'Pioneer of Libyan caricature and visual social commentary whose works chronicled cultural transitions.',
            'birth_date' => '1936-01-01',
            'death_date' => '2011-06-05',
            'avatar_url' => 'artists/mohamed-zwawi.jpg',
            'is_featured' => true,
        ]);

        $artist2 = ArtistProfile::create([
            'full_name' => 'Ali Ahmed Bakathir',
            'slug' => 'ali-ahmed-bakathir',
            'art_category' => ArtCategory::Theatre,
            'biography' => 'Distinguished Arab dramatist, novelist, and poet renowned for epic theatrical dialogues and poetic theatre.',
            'birth_date' => '1910-12-21',
            'death_date' => '1969-11-10',
            'avatar_url' => 'artists/ali-ahmed-bakathir.jpg',
            'is_featured' => true,
        ]);

        $artist3 = ArtistProfile::create([
            'full_name' => 'Ali Ghadamsi',
            'slug' => 'ali-ghadamsi',
            'art_category' => ArtCategory::Cinema,
            'biography' => 'Cinematographer and director focusing on Libyan desert folklore, architectural preservation, and visual history.',
            'birth_date' => '1952-03-14',
            'death_date' => null,
            'avatar_url' => 'artists/ali-ghadamsi.jpg',
            'is_featured' => false,
        ]);

        // 5. Articles
        Article::create([
            'title' => 'Visual Memory in the Works of Mohamed Zwawi',
            'slug' => 'visual-memory-works-mohamed-zwawi',
            'excerpt' => 'An in-depth critical analysis of satirical fine art and Libyan national identity.',
            'content' => 'Zwawi’s caricatures transcended typical humorous sketches, functioning as meticulous historical records of architectural nuances, traditional costumes, and social transformation across twentieth-century Libya.',
            'featured_image' => 'articles/zwawi-retrospective.jpg',
            'category_id' => $fineArts->id,
            'issue_id' => $issue1->id,
            'author_id' => $chiefEditor->id,
            'artist_profile_id' => $artist1->id,
            'status' => PublicationStatus::Published,
            'views_count' => 1250,
            'published_at' => now()->subMonths(3),
        ]);

        Article::create([
            'title' => 'The Poetics of Classical Dialogue in Modern Libyan Drama',
            'slug' => 'poetics-dialogue-modern-libyan-drama',
            'excerpt' => 'Exploring dramatic structure and historical dramatization in twentieth-century scripts.',
            'content' => 'The fusion of regional rhythm with classic dramatic meters provided a distinct identity to theatrical productions staged throughout Tripoli and Benghazi playhouses.',
            'featured_image' => 'articles/stage-critique.jpg',
            'category_id' => $theatre->id,
            'issue_id' => $issue2->id,
            'author_id' => $theatreEditor->id,
            'artist_profile_id' => $artist2->id,
            'status' => PublicationStatus::Published,
            'views_count' => 840,
            'published_at' => now()->subMonth(),
        ]);

        Article::create([
            'title' => 'Documenting the Sahara: Visual Scales in Southern Libyan Cinema',
            'slug' => 'documenting-sahara-southern-libyan-cinema',
            'excerpt' => 'Desert landscapes as an active character in documentary cinema.',
            'content' => 'Lighting conditions, natural acoustic ambiance, and spatial depth create a poetic language unique to Saharan visual narratives.',
            'featured_image' => 'articles/desert-cinema.jpg',
            'category_id' => $cinema->id,
            'issue_id' => $issue3->id,
            'author_id' => $cinemaEditor->id,
            'artist_profile_id' => $artist3->id,
            'status' => PublicationStatus::Review,
            'views_count' => 45,
            'published_at' => null,
        ]);

        // 6. Media Gallery
        MediaGallery::create([
            'title' => 'Zwawi Caricature Retrospective Exhibition (1975)',
            'slug' => 'zwawi-caricature-retrospective-1975',
            'type' => MediaType::Image,
            'file_path' => 'media/gallery/zwawi_original_1975.jpg',
            'thumbnail_path' => 'media/gallery/thumbs/zwawi_original_1975_thumb.jpg',
            'duration_seconds' => null,
            'size_bytes' => 4820300,
            'description' => 'Original high-resolution scan from the General Authority archival collection.',
        ]);

        MediaGallery::create([
            'title' => 'Traditional Ma’luf Musical Recording from Tripoli Old Town',
            'slug' => 'maluf-recording-tripoli-old-town',
            'type' => MediaType::Audio,
            'file_path' => 'media/audio/maluf_suite_1982.mp3',
            'thumbnail_path' => null,
            'duration_seconds' => 1420,
            'size_bytes' => 34078720,
            'description' => 'Archival field recording of Andalusian Ma’luf performed by the Tripoli Heritage Ensemble.',
        ]);

        MediaGallery::create([
            'title' => 'Behind the Scenes: National Theatre Festival Documentation',
            'slug' => 'behind-the-scenes-national-theatre-festival',
            'type' => MediaType::Video,
            'file_path' => 'media/video/theatre_festival_doc_2026.mp4',
            'thumbnail_path' => 'media/video/thumbs/theatre_doc_thumb.jpg',
            'duration_seconds' => 780,
            'size_bytes' => 245890000,
            'description' => 'Documentary feature showcasing stage rehearsals and set design construction.',
        ]);

        MediaGallery::create([
            'title' => 'Special Supplement: Libyan Cinema History (1965-2025)',
            'slug' => 'libyan-cinema-history-supplement-pdf',
            'type' => MediaType::Pdf,
            'file_path' => 'media/documents/cinema_history_supplement.pdf',
            'thumbnail_path' => null,
            'duration_seconds' => null,
            'size_bytes' => 18450000,
            'description' => 'Complete digital publication with historical photography and filmography catalog.',
        ]);
    }
}
