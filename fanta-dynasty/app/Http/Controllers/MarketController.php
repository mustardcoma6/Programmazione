public function storeSession(Request $request) {
        $participant = LeagueParticipant::where('user_id', auth()->id())->first();
        
        $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'auction_time' => 'required', // Riceviamo HH:MM
            'roles' => 'required|array|min:1'
        ]);

        // TRUCCO: Trasformiamo HH:MM in minuti totali
        // Esempio: "02:30" -> (2 * 60) + 30 = 150 minuti
        $timeParts = explode(':', $request->auction_time);
        $totalMinutes = ($timeParts[0] * 60) + $timeParts[1];

        MarketSession::create([
            'league_id' => $participant->league_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'auction_duration' => $totalMinutes, // Salviamo come numero
            'allowed_roles' => implode(',', $request->roles)
        ]);

        return back()->with('message', 'Sessione salvata con durata asta personalizzata!');
    }