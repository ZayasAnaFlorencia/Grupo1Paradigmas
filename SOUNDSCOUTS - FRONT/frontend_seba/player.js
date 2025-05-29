// Datos de ejemplo (reemplazar por llamadas a tu backend)
const songs = [
    {
        title: "Bohemian Rhapsody",
        artist: "Queen",
        duration: "4:15",
        cover: "https://i.scdn.co/image/ab67616d00001e02ff9ca10b55ce82ae553c8228",
        audioSrc: "" // Agregar URL del archivo de audio si es necesario
    },
    {
        title: "Blinding Lights",
        artist: "The Weeknd",
        duration: "3:20",
        cover: "https://i.scdn.co/image/ab67616d00001e02a3a0d58a7a2a9a0e8a0b9e9e"
    }
];

// Clase principal del reproductor
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
        this.currentSong = (this.currentSong + 1) % songs.length;
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
}

// Inicializar el reproductor cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const player = new MusicPlayer();
});