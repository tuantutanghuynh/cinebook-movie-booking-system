import { useEffect, useState } from "react";
import api from "../services/api";

export default function HomePage() {
  const [movies, setMovies] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get("/movies")
      .then((res) => {
        setMovies(res.data.movies);
        setLoading(false);
      })
      .catch((err) => {
        console.error(err);
        setLoading(false);
      });
  }, []);

  if (loading) {
    return <p>Loading...</p>;
  }
  return (
    <div>
      <h1>Now Showing</h1>
      {movies.map((movie) => (
        <div key={movie.id}>
          <img src={movie.poster_url} alt={movie.title} width={150} />
          <h3>{movie.title}</h3>
          <p>{movie.genres.join(", ")}</p>
        </div>
      ))}
    </div>
  );
}
