<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Naruto Card Game</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
        :root{
            --bg: linear-gradient(135deg,#ffecd2,#fcb69f);
            --card-size: 100px;
            --card-radius: 12px;
            --card-gap: 12px;
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            font-family: "Segoe UI", Roboto, Arial, sans-serif;
            background: var(--bg);
            padding:24px;
        }
        .container{
            width:100%;
            max-width:760px;
            background: rgba(255,255,255,0.9);
            border-radius:18px;
            padding:20px;
            box-shadow:0 8px 30px rgba(0,0,0,0.15);
            text-align:center;
        }

        h1{margin:6px 0 8px;font-size:24px;color:#333}
        p.lead{margin:0 0 14px;color:#555}

        /* .top-row{
            display:flex;
            gap:10px;
            align-items:center;
            justify-content:space-between;
            margin-bottom:14px;
            flex-wrap:wrap;
        } */
        
        /* .stats {
            display:flex;
            gap:12px;
            align-items:center;
            color:#333;
            font-weight:600;
        } */

        .btn{
            background:#00d4ff;
            color:#003;
            border:none;
            padding:10px 14px;
            border-radius:10px;
            cursor:pointer;
            font-weight:700;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .btn:hover{transform:translateY(-3px); box-shadow:0 6px 18px rgba(0,0,0,0.12);}

        /* board */
        .board{
            display:grid;
            gap: var(--card-gap);
            justify-content:center;
            margin-top:12px;
            margin-bottom:12px;
            /* change columns with JS (we will set to 4 or 3) */
            grid-template-columns: repeat(4, var(--card-size));
        }

        .card {
            width: var(--card-size);
            height: var(--card-size);
            perspective: 800px;
        }

        .inner {
            width:100%;
            height:100%;
            position:relative;
            transform-style: preserve-3d;
            transition: transform 400ms;
            border-radius: var(--card-radius);
            cursor: pointer;
        }

        .flipped .inner {
            transform: rotateY(180deg);
        }

        .face {
            position:absolute;
            inset:0;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius: var(--card-radius);
            backface-visibility: hidden;
            font-size:36px;
            user-select:none;
        }

        .front {
            background: linear-gradient(135deg,#a56767,#797a4b);
            color:#333;
            transform: rotateY(180deg);
            border: 3px solid #fff;
            box-shadow: 0 6px 12px rgba(0,0,0,0.06);
        }

        .back {
            background: linear-gradient(135deg,#00d4ff,#0066cc);
            color:white;
            font-size:28px;
            border: 3px solid rgba(255,255,255,0.12);
            box-shadow: 0 8px 18px rgba(0,0,0,0.12);
        }

        .matched .inner {
            transform: rotateY(180deg);
            cursor: default;
            box-shadow: 0 0 0 3px rgba(0,255,120,0.12) inset;
        }

        .message {
            margin-top:12px;
            font-weight:700;
            color:#0b3;
        }

        /* responsive smaller screens */
        @media (max-width:520px){
            :root{--card-size:80px}
            .board{grid-template-columns: repeat(3, var(--card-size));}
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧠 Memory Matching Game</h1>
        <p class="lead">Find all matching pairs. Good luck!</p>
        
            {{-- <div class="stats">
                <div>Tries: <span id="tries">0</span></div>
                <div>Matches: <span id="matches">0</span>/<span id="totalPairs">0</span></div>
            </div> --}}

            <div>
                <button class="btn" id="resetBtn">Restart</button>
            </div>

        <div class="board" id="board"></div>

        <div id="message" class="message" role="status" aria-live="polite"></div>
    </div>

    <script>
        // ----- GAME SETTINGS -----
        const pairs = 6; // number of pairs
        
        const IMAGES = [
            "/images/1.png",
            "/images/2.png",
            "/images/3.png",
            "/images/4.png",
            "/images/5.png",
            "/images/6.png"
        ];

        const sounds = {
            flip: new Audio('/sounds/flip.mp3'),
            match: new Audio('/sounds/match.mp3'),
            win: new Audio('/sounds/win.mp3')
        };

        // const bgm = new Audio('/sounds/bgm.mp3');
        // bgm.loop = true;     // repeat forever
        // bgm.volume = 0.4;   // 0.0 – 1.0


        // let bgmStarted = false;

        // function startBGM() {
        //     if (!bgmStarted) {
        //         bgm.play().catch(() => {});
        //         bgmStarted = true;
        //     }
        // }



        // ----- STATE -----
        let firstCard = null;
        let secondCard = null;
        let lockBoard = false;
        //let tries = 0;
        let matches = 0;
        let totalPairs = pairs;

        const board = document.getElementById('board');
        // const triesEl = document.getElementById('tries');
        // const matchesEl = document.getElementById('matches');
        // const totalPairsEl = document.getElementById('totalPairs');
        const messageEl = document.getElementById('message');
        const resetBtn = document.getElementById('resetBtn');

        // totalPairsEl.textContent = totalPairs;

        // Fisher-Yates shuffle
        function shuffle(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
            return array;
        }

        // Build cards array (pairs) and shuffle
        function buildDeck() {
            const chosen = IMAGES.slice(0); // copy
            shuffle(chosen);
            const selected = chosen.slice(0, totalPairs);
            const deck = selected.concat(selected); // duplicate for pairs
            return shuffle(deck);
        }

        // Render board
        function renderBoard() {
            board.innerHTML = '';
            firstCard = null;
            secondCard = null;
            lockBoard = false;
            //tries = 0;
            matches = 0;
            // triesEl.textContent = tries;
            // matchesEl.textContent = matches;
            messageEl.textContent = '';

            // adjust grid columns for even layout
            const cols = (window.innerWidth <= 520) ? 3 : 4;
            board.style.gridTemplateColumns = `repeat(${cols}, var(--card-size))`;

            const deck = buildDeck();
            deck.forEach((image, index) => {
                const card = document.createElement('div');
                card.className = 'card';
                card.dataset.value = image;
                card.dataset.index = index;

                // inner (for flip)
                const inner = document.createElement('div');
                inner.className = 'inner';

                const back = document.createElement('div');
                back.className = 'face back';
                back.textContent = '❓';

                const front = document.createElement('div');
                front.className = 'face front';
                const img = document.createElement('img');
                img.src = image;
                img.style.width = "70px";
                img.style.height = "70px";
                front.appendChild(img);


                inner.appendChild(front);
                inner.appendChild(back);
                card.appendChild(inner);

                // click handler
                card.addEventListener('click', onCardClick);

                board.appendChild(card);
            });
        }

        // Card click handler
        function onCardClick(e) {
            // startBGM();
            if (lockBoard) return;
            const card = e.currentTarget;
            if (card.classList.contains('flipped') || card.classList.contains('matched')) return;

            card.classList.add('flipped');
            playSound(sounds.flip);

            if (!firstCard) {
                firstCard = card;
                return;
            }

            secondCard = card;
            lockBoard = true;
            //tries++;
            // triesEl.textContent = tries;

            // check match
            if (firstCard.dataset.value === secondCard.dataset.value) {
                playSound(sounds.match);
                // matched
                
                firstCard.classList.add('matched');
                secondCard.classList.add('matched');
                matches++;
                
                //matchesEl.textContent = matches;
                // reset selections
                firstCard = null;
                secondCard = null;
                lockBoard = false;

                // win check
                if (matches === totalPairs) {
                    playSound(sounds.win);
                    messageEl.textContent = "🎉 You won! Great job!";
                }
            } else {
                // not match -> flip back after delay
                setTimeout(() => {
                    firstCard.classList.remove('flipped');
                    secondCard.classList.remove('flipped');
                    firstCard = null;
                    secondCard = null;
                    lockBoard = false;
                }, 700);
            }
        }

        function playSound(sound) {
            sound.currentTime = 0;
            sound.play().catch(() => {});
        }

        

        // Restart game
        resetBtn.addEventListener('click', () => {
            playSound(sounds.flip);
            renderBoard();
        });

        // initial render
        renderBoard();

        // optional: keyboard accessibility: space to reset
        document.addEventListener('keydown', (e) => {
            if (e.key === 'r' || e.key === 'R') renderBoard();
        });
    </script>
</body>
</html>
