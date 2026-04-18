<script>
    let count = $state(0);

    function increment() {
        count += 1;
    }

    function decrement() {
        count -= 1;
    }
</script>

<div class="svelte-app">
    <h2>Svelte Component</h2>
    <p>This is a Svelte app integrated with OroPlatform!</p>

    <div class="counter">
        <button onclick={decrement}>-</button>
        <span class="count">{count}</span>
        <button onclick={increment}>+</button>
    </div>
</div>

<style>
    .svelte-app {
        padding: 20px;
        border: 2px solid #ff3e00;
        border-radius: 8px;
        background: linear-gradient(135deg, #fff5f2 0%, #fff 100%);
        margin: 20px 0;
    }

    h2 {
        color: #ff3e00;
        margin: 0 0 10px 0;
    }

    .counter {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 15px;
    }

    button {
        width: 40px;
        height: 40px;
        font-size: 20px;
        border: none;
        border-radius: 50%;
        background: #ff3e00;
        color: white;
        cursor: pointer;
        transition: transform 0.1s;
    }

    button:hover {
        transform: scale(1.1);
    }

    button:active {
        transform: scale(0.95);
    }

    .count {
        font-size: 24px;
        font-weight: bold;
        min-width: 50px;
        text-align: center;
    }
</style>
