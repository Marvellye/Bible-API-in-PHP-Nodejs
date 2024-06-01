<?php
// Set headers to allow CORS and return JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Get the request URI and parse it, removing empty elements caused by trailing slashes
$request_uri = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/')));

// Function to load and decode JSON file
function load_book_data($book, $version = '') {
    $file_path = ($version ? "./$version/" : "./bibles/") . ucfirst($book) . ".json";
    if (!file_exists($file_path)) {
        return null;
    }
    $book_content = file_get_contents($file_path);
    return json_decode($book_content, true);
}

// Function to get a random book name from the given directory
function get_random_book($version = '') {
    $directory = $version ? "./$version/" : "./bibles/";
    $files = glob($directory . '*.json');
    if (empty($files)) {
        return null;
    }
    $random_file = $files[array_rand($files)];
    return pathinfo($random_file, PATHINFO_FILENAME);
}

// Function to get a random element from an array
function get_random_element($array) {
    return $array[array_rand($array)];
}

// Function to handle a specific book request
function handle_book_request($book, $version = '') {
    $book_data = load_book_data($book, $version);
    if (!$book_data) {
        http_response_code(404);
        echo json_encode(["error" => "Book not found"]);
        exit;
    }
    $chapters = [];
    foreach ($book_data['chapters'] as $chapter_entry) {
        $chapters[] = [
            'chapter' => $chapter_entry['chapter'],
            'verses_count' => count($chapter_entry['verses']),
            'verses' => $chapter_entry['verses']
        ];
    }
    echo json_encode([
        "book" => $book,
        "chapter_count" => count($book_data['chapters']),
        "chapters" => $chapters
    ]);
}

// Function to handle a specific chapter request
function handle_chapter_request($book, $chapter, $version = '') {
    $book_data = load_book_data($book, $version);
    if (!$book_data) {
        http_response_code(404);
        echo json_encode(["error" => "Book not found"]);
        exit;
    }
    $chapter_data = null;
    foreach ($book_data['chapters'] as $chapter_entry) {
        if ($chapter_entry['chapter'] == $chapter) {
            $chapter_data = $chapter_entry;
            break;
        }
    }
    if (!$chapter_data) {
        http_response_code(404);
        echo json_encode(["error" => "Chapter not found"]);
        exit;
    }
    echo json_encode([
        "book" => $book,
        "chapter" => $chapter,
        "verses_count" => count($chapter_data['verses']),
        "verses" => $chapter_data['verses']
    ]);
}

// Function to handle a specific verse request
function handle_verse_request($book, $chapter, $verse, $version = '') {
    $book_data = load_book_data($book, $version);
    if (!$book_data) {
        http_response_code(404);
        echo json_encode(["error" => "Book not found"]);
        exit;
    }
    $chapter_data = null;
    foreach ($book_data['chapters'] as $chapter_entry) {
        if ($chapter_entry['chapter'] == $chapter) {
            $chapter_data = $chapter_entry;
            break;
        }
    }
    if (!$chapter_data) {
        http_response_code(404);
        echo json_encode(["error" => "Chapter not found"]);
        exit;
    }
    $verse_data = null;
    foreach ($chapter_data['verses'] as $verse_entry) {
        if ($verse_entry['verse'] == $verse) {
            $verse_data = $verse_entry;
            break;
        }
    }
    if (!$verse_data) {
        http_response_code(404);
        echo json_encode(["error" => "Verse not found"]);
        exit;
    }
    echo json_encode([
        "book" => $book,
        "chapter" => $chapter,
        "verse" => $verse,
        "content" => $verse_data['text']
    ]);
}

// Handle cases based on the number of URL segments
switch (count($request_uri)) {
    case 0:
        // Root URL - show welcome message
        echo json_encode([
            "message" => "Hello Wary TravellerðŸ‘‹, This is a free open source Bible API. Here are the usage guidelines:",
            "guidelines" => [
                "To get the content of a specific book: /{book}",
                "To get the content of a specific chapter: /{book}/{chapter}",
                "To get the content of a specific verse: /{book}/{chapter}/{verse}",
                "To get a random book: /random",
                "To get a random chapter: /random/chapter",
                "To get a random verse: /random/chapter/verse",
                "To get the content of a specific book from 1611 KJV: /1611/{book}",
                "To get the content of a specific chapter from 1611 KJV: /1611/{book}/{chapter}",
                "To get the content of a specific verse from 1611 KJV: /1611/{book}/{chapter}/{verse}",
                "To get a random book from 1611 KJV: /1611/random",
                "To get a random chapter from 1611 KJV: /1611/random/chapter",
                "To get a random verse from 1611 KJV: /1611/random/chapter/verse"
            ],
            "thanks" => "Thanks for your considered support",
            "credits" => "https://aruljohn.com/"
        ]);
        break;

    case 1:
        if ($request_uri[0] === 'random') {
            $book = get_random_book();
            if (!$book) {
                http_response_code(404);
                echo json_encode(["error" => "No books available"]);
                exit;
            }
            handle_book_request($book);
        } elseif ($request_uri[0] === '1611') {
            $books_file = "./1611b/Books.json";
            if (!file_exists($books_file)) {
                http_response_code(404);
                echo json_encode(["error" => "Books list not found"]);
                exit;
            }
            $books_content = file_get_contents($books_file);
            $books = json_decode($books_content, true);
            echo json_encode($books);
        } else {
            $book = $request_uri[0];
            handle_book_request($book);
        }
        break;

    case 2:
        if ($request_uri[0] === 'random' && $request_uri[1] === 'chapter') {
            $book = get_random_book();
            if (!$book) {
                http_response_code(404);
                echo json_encode(["error" => "No books available"]);
                exit;
            }
            $book_data = load_book_data($book);
            $chapter_data = get_random_element($book_data['chapters']);
            echo json_encode([
                "book" => $book,
                "chapter" => $chapter_data['chapter'],
                "verses_count" => count($chapter_data['verses']),
                "verses" => $chapter_data['verses']
            ]);
        } elseif ($request_uri[0] === '1611') {
            $book = $request_uri[1];
            handle_book_request($book, '1611b');
        } else {
            $book = $request_uri[0];
            $chapter = $request_uri[1];
            handle_chapter_request($book, $chapter);
        }
        break;

    case 3:
        if ($request_uri[0] === 'random' && $request_uri[1] === 'chapter' && $request_uri[2] === 'verse') {
            $book = get_random_book();
            if (!$book) {
                http_response_code(404);
                echo json_encode(["error" => "No books available"]);
                exit;
            }
            $book_data = load_book_data($book);
            $random_chapter = get_random_element($book_data['chapters']);
            $random_verse = get_random_element($random_chapter['verses']);
            echo json_encode([
                "book" => $book,
                "chapter" => $random_chapter['chapter'],
                "verse" => $random_verse['verse'],
                "content" => $random_verse['text']
            ]);
        } elseif ($request_uri[0] === '1611' && $request_uri[1] === 'random' && $request_uri[2] === 'chapter') {
            $book = get_random_book('1611b');
            if (!$book) {
                http_response_code(404);
                echo json_encode(["error" => "No books available"]);
                exit;
            }
            $book_data = load_book_data($book, '1611b');
            $chapter_data = get_random_element($book_data['chapters']);
            echo json_encode([
                "book" => $book,
                "chapter" => $chapter_data['chapter'],
                "verses_count" => count($chapter_data['verses']),
                "verses" => $chapter_data['verses']
            ]);
        } elseif ($request_uri[0] === '1611' && $request_uri[1] === 'random' && $request_uri[2] === 'chapter' && $request_uri[3] === 'verse') {
            $book = get_random_book('1611b');
            if (!$book) {
                http_response_code(404);
                echo json_encode(["error" => "No books available"]);
                exit;
            }
            $book_data = load_book_data($book, '1611b');
            $random_chapter = get_random_element($book_data['chapters']);
            $random_verse = get_random_element($random_chapter['verses']);
            echo json_encode([
                "book" => $book,
                "chapter" => $random_chapter['chapter'],
                "verse" => $random_verse['verse'],
                "content" => $random_verse['text']
            ]);
        } elseif ($request_uri[0] === '1611') {
            $book = $request_uri[1];
            $chapter = $request_uri[2];
            handle_chapter_request($book, $chapter, '1611b');
        } else {
            $book = $request_uri[0];
            $chapter = $request_uri[1];
            $verse = $request_uri[2];
            handle_verse_request($book, $chapter, $verse);
        }
        break;

    case 4:
        if ($request_uri[0] === '1611' && $request_uri[1] === 'random' && $request_uri[2] === 'chapter' && $request_uri[3] === 'verse') {
            $book = get_random_book('1611b');
            if (!$book) {
                http_response_code(404);
                echo json_encode(["error" => "No books available"]);
                exit;
            }
            $book_data = load_book_data($book, '1611b');
            $random_chapter = get_random_element($book_data['chapters']);
            $random_verse = get_random_element($random_chapter['verses']);
            echo json_encode([
                "book" => $book,
                "chapter" => $random_chapter['chapter'],
                "verse" => $random_verse['verse'],
                "content" => $random_verse['text']
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["error" => "Invalid URL. Usage: /book or /book/chapter or /book/chapter/verse or /random or /random/chapter or /random/chapter/verse or /1611/{book}/{chapter}/{verse}"]);
        break;
}
?>
