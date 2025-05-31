const songs = [
    {
        title: "Bohemian Rhapsody",
        artist: "Queen",
        duration: "5:58",
        cover: "https://i.scdn.co/image/ab67616d0000b273e1b68f28db6365c387dc6a03",
        audioSrc: "Queen - Bohemian Rhapsody.mp3" // Agregar URL del archivo de audio si es necesario
    },
    {
        title: "Blinding Lights",
        artist: "The Weeknd",
        duration: "3:23",
        cover: "https://i.scdn.co/image/ab67616d0000b273a3eff72f62782fb589a492f9",
        audioSrc: "The Weeknd - Blinding Lights.mp3"
    }
];

// Cola FIFO para sugerencias
class SongQueue {
    constructor() {
        this.queue = [];
    }

    enqueue(song) {
        this.queue.push(song);
    }

    dequeue() {
        return this.queue.shift();
    }

    isEmpty() {
        return this.queue.length === 0;
    }

    clear() {
        this.queue = [];
    }
}

class MusicPlayer {
    constructor() {
        this.player = document.getElementById('player');
        this.playBtn = document.getElementById('play-btn');
        this.prevBtn = document.getElementById('prev-btn');
        this.nextBtn = document.getElementById('next-btn');
        this.progressBar = document.getElementById('progress-bar');
        this.progress = document.getElementById('progress');
        this.currentTimeEl = document.getElementById('current-time');
        this.durationEl = document.getElementById('duration');
        this.volumeBar = document.getElementById('volume-bar');
        this.volumeProgress = document.getElementById('volume-progress');
        this.songTitle = document.getElementById('song-title');
        this.songArtist = document.getElementById('song-artist');
        this.albumCover = document.getElementById('album-cover');

        this.isPlaying = false;
        this.currentSong = 0;
        this.volume = 0.8;
        this.audio = new Audio();

        this.songQueue = new SongQueue();

        this.init();
    }

    init() {
        this.loadSong();
        this.setupEventListeners();
    }

    loadSong() {
        const song = songs[this.currentSong];
        this.songTitle.textContent = song.title;
        this.songArtist.textContent = song.artist;
        this.durationEl.textContent = song.duration;
        this.albumCover.src = song.cover;

        if (song.audioSrc) {
            this.audio.src = song.audioSrc;
        } else {
            this.audio.src = ''; // Silencio si no hay fuente
        }
    }

    setupEventListeners() {
        this.playBtn.addEventListener('click', () => this.togglePlay());
        this.prevBtn.addEventListener('click', () => this.prevSong());
        this.nextBtn.addEventListener('click', () => this.nextSong());
        this.progressBar.addEventListener('click', (e) => this.setProgress(e));
        this.volumeBar.addEventListener('click', (e) => this.setVolume(e));

        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.nextSong());
    }

    togglePlay() {
        this.isPlaying ? this.pauseSong() : this.playSong();
    }

    playSong() {
        this.isPlaying = true;
        this.player.classList.add('playing');
        this.playBtn.innerHTML = '⏸';
        this.audio.play();
    }

    pauseSong() {
        this.isPlaying = false;
        this.player.classList.remove('playing');
        this.playBtn.innerHTML = '▶';
        this.audio.pause();
    }

    nextSong() {
        if (!this.songQueue.isEmpty()) {
            const nextFromQueue = this.songQueue.dequeue();
            songs.push(nextFromQueue);
            this.currentSong = songs.length - 1;
        } else {
            this.currentSong = (this.currentSong + 1) % songs.length;
        }

        this.loadSong();
        if (this.isPlaying) this.playSong();
    }

    prevSong() {
        this.currentSong = (this.currentSong - 1 + songs.length) % songs.length;
        this.loadSong();
        if (this.isPlaying) this.playSong();
    }

    updateProgress() {
        const { currentTime, duration } = this.audio;
        const progressPercent = (currentTime / duration) * 100;
        this.progress.style.width = `${progressPercent}%`;
        this.currentTimeEl.textContent = this.formatTime(currentTime);
    }

    setProgress(e) {
        const width = this.progressBar.clientWidth;
        const clickX = e.offsetX;
        const duration = this.audio.duration;
        this.audio.currentTime = (clickX / width) * duration;
    }

    setVolume(e) {
        const width = this.volumeBar.clientWidth;
        const clickX = e.offsetX;
        this.volume = clickX / width;
        this.volume = Math.max(0, Math.min(1, this.volume));
        this.volumeProgress.style.width = `${this.volume * 100}%`;
        this.audio.volume = this.volume;
    }

    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }

    // Método público para agregar sugerencias a la cola
    suggestSong(song) {
        this.songQueue.enqueue(song);
    }
}

// Inicializar el reproductor y ejemplo de uso de sugerencia
document.addEventListener('DOMContentLoaded', () => {
    const player = new MusicPlayer();

    // Ejemplo: añadir una canción sugerida a la cola
    player.suggestSong({
        title: "Another One Bites the Dust",
        artist: "Queen",
        duration: "3:35",
        cover: "https://i.scdn.co/image/ab67616d00001e0218a74e90a5d5c4a4d19e3a8e",
        audioSrc: "" // Agregar URL si tienes audio
    });

    // Puedes llamar a player.suggestSong(...) desde botones u otros eventos
});
