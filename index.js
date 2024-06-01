const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const port = 3000;

app.use(cors());
app.use(express.json());

const loadBookData = (book, version = '') => {
  const filePath = version ? `./${version}/${book}.json` : `./bibles/${book}.json`;
  if (!fs.existsSync(filePath)) {
    return null;
  }
  const bookContent = fs.readFileSync(filePath, 'utf-8');
  return JSON.parse(bookContent);
};

const getRandomBook = (version = '') => {
  const directory = version ? `./${version}/` : './bibles/';
  const files = fs.readdirSync(directory).filter(file => file.endsWith('.json'));
  if (files.length === 0) {
    return null;
  }
  const randomFile = files[Math.floor(Math.random() * files.length)];
  return path.basename(randomFile, '.json');
};

const getRandomElement = (array) => array[Math.floor(Math.random() * array.length)];

const handleBookRequest = (res, book, version = '') => {
  const bookData = loadBookData(book, version);
  if (!bookData) {
    res.status(404).json({ error: 'Book not found' });
    return;
  }
  const chapters = bookData.chapters.map(chapter => ({
    chapter: chapter.chapter,
    verses_count: chapter.verses.length,
    verses: chapter.verses
  }));
  res.json({
    book,
    chapter_count: bookData.chapters.length,
    chapters
  });
};

const handleChapterRequest = (res, book, chapter, version = '') => {
  const bookData = loadBookData(book, version);
  if (!bookData) {
    res.status(404).json({ error: 'Book not found' });
    return;
  }
  const chapterData = bookData.chapters.find(ch => ch.chapter === parseInt(chapter, 10));
  if (!chapterData) {
    res.status(404).json({ error: 'Chapter not found' });
    return;
  }
  res.json({
    book,
    chapter,
    verses_count: chapterData.verses.length,
    verses: chapterData.verses
  });
};

const handleVerseRequest = (res, book, chapter, verse, version = '') => {
  const bookData = loadBookData(book, version);
  if (!bookData) {
    res.status(404).json({ error: 'Book not found' });
    return;
  }
  const chapterData = bookData.chapters.find(ch => ch.chapter === parseInt(chapter, 10));
  if (!chapterData) {
    res.status(404).json({ error: 'Chapter not found' });
    return;
  }
  const verseData = chapterData.verses.find(v => v.verse === parseInt(verse, 10));
  if (!verseData) {
    res.status(404).json({ error: 'Verse not found' });
    return;
  }
  res.json({
    book,
    chapter,
    verse,
    content: verseData.text
  });
};

app.get('/', (req, res) => {
  res.json({
    message: "Hello Wary Traveller, This is a free open source Bible API. Here are the usage guidelines:",
    guidelines: [
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
    thanks: "Thanks for your considered support",
    credits: "https://aruljohn.com/"
  });
});

app.get('/:book', (req, res) => {
  const { book } = req.params;
  if (book === 'random') {
    const randomBook = getRandomBook();
    if (!randomBook) {
      res.status(404).json({ error: 'No books available' });
      return;
    }
    handleBookRequest(res, randomBook);
  } else if (book === '1611') {
    const booksFile = './1611b/Books.json';
    if (!fs.existsSync(booksFile)) {
      res.status(404).json({ error: 'Books list not found' });
      return;
    }
    const booksContent = fs.readFileSync(booksFile, 'utf-8');
    res.json(JSON.parse(booksContent));
  } else {
    handleBookRequest(res, book);
  }
});

app.get('/:book/:chapter', (req, res) => {
  const { book, chapter } = req.params;
  if (book === 'random' && chapter === 'chapter') {
    const randomBook = getRandomBook();
    if (!randomBook) {
      res.status(404).json({ error: 'No books available' });
      return;
    }
    const bookData = loadBookData(randomBook);
    const chapterData = getRandomElement(bookData.chapters);
    res.json({
      book: randomBook,
      chapter: chapterData.chapter,
      verses_count: chapterData.verses.length,
      verses: chapterData.verses
    });
  } else if (book === '1611') {
    handleBookRequest(res, chapter, '1611b');
  } else {
    handleChapterRequest(res, book, chapter);
  }
});

app.get('/:book/:chapter/:verse', (req, res) => {
  const { book, chapter, verse } = req.params;
  if (book === 'random' && chapter === 'chapter' && verse === 'verse') {
    const randomBook = getRandomBook();
    if (!randomBook) {
      res.status(404).json({ error: 'No books available' });
      return;
    }
    const bookData = loadBookData(randomBook);
    const randomChapter = getRandomElement(bookData.chapters);
    const randomVerse = getRandomElement(randomChapter.verses);
    res.json({
      book: randomBook,
      chapter: randomChapter.chapter,
      verse: randomVerse.verse,
      content: randomVerse.text
    });
  } else if (book === '1611') {
    handleVerseRequest(res, chapter, verse, req.params.version, '1611b');
  } else {
    handleVerseRequest(res, book, chapter, verse);
  }
});

app.listen(port, () => {
  console.log(`Bible API listening at http://localhost:${port}`);
});
