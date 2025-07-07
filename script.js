const cells = document.querySelectorAll("td");
    const st = document.getElementById("status");
    let currentPlayer = "X";
    let gameState = ["","","","","","","","",""];
    let gameActive = true;

    const winningConditions = [
      [0,1,2],[3,4,5],[6,7,8], 
      [0,3,6],[1,4,7],[2,5,8],
      [0,4,8],[2,4,6]
    ];

    cells.forEach(cell => cell.addEventListener("click", handleCellClick));

    function handleCellClick(e) {
      const i = e.target.getAttribute("data-index");
      if (gameState[i] !== "" || !gameActive) return;

      gameState[i] = currentPlayer;
      e.target.innerText = currentPlayer;

      checkResult();
    }

    function checkResult() {
      for (const d of winningConditions) {
        const [a,b,c] = d;
        if (gameState[a] && gameState[a] === gameState[b] && gameState[b] === gameState[c]) {
          st.innerText = `Player ${currentPlayer === "X" ? "1 (X)" : "2 (O)"} wins!`;
          gameActive = false;
          return;
        }
      }

      if (!gameState.includes("")) {
        st.innerText = "Draw!";
        gameActive = false;
        return;
      }

      currentPlayer = currentPlayer === "X" ? "O" : "X";
      st.innerText = `Player ${currentPlayer === "X" ? "1's turn (X)" : "2's turn (O)"}`;
    }

    function resetGame() {
      gameState = ["","","","","","","","",""];
      cells.forEach(cell => cell.innerText = "");
      currentPlayer = "X";
      gameActive = true;
      st.innerText = "Player 1's turn (X)";
    }